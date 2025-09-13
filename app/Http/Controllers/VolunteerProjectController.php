<?php

namespace App\Http\Controllers;

use App\Models\CommunityProject;
use App\Models\ProjectApplication; // 你的申请模型；如命名不同请改
use App\Models\User;
use App\Models\SystemLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class VolunteerProjectController extends Controller
{
    // 项目列表（Inertia 渲染）
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = CommunityProject::query()
            ->where('status', '!=', 'cancelled');
        
        // 应用过滤器
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }
        
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }
        
        $projects = $query->orderBy('date', 'asc')->paginate(8)
            ->through(function ($project) use ($user) {
                // 为每个项目添加用户的申请状态
                if ($user) {
                    $application = ProjectApplication::where('user_id', $user->id)
                        ->where('community_project_id', $project->id)
                        ->first();
                    
                    $project->user_application_status = $application ? $application->status : null;
                    $project->user_application = $application;
                } else {
                    $project->user_application_status = null;
                    $project->user_application = null;
                }
                
                // 确保 photo_url 访问器被调用
                $project->photo_url = $project->photo_url;
                
                // Debug: 直接设置 photo_url 确保前端能接收到
                if ($project->photo_paths && count($project->photo_paths) > 0) {
                    $project->photo_url = asset('storage/' . $project->photo_paths[0]);
                } else {
                    $project->photo_url = null;
                }
                
                // 添加容量信息
                $project->is_full = $project->isFull();
                $project->available_spots = $project->available_spots;
                $project->progress_percentage = $project->progress_percentage;
                
                return $project;
            });

        // 返回 Inertia 页面（你的 Projects.vue）
        return inertia('Volunteer/Projects', [
            'projects' => $projects,
        ]);
    }

    // 项目详情（可选）
    public function show(CommunityProject $project)
    {
        return inertia('ProjectShow', [
            'project' => $project,
        ]);
    }

    // 申请参与（Inertia 表单）
    public function apply(Request $request, CommunityProject $project)
    {
        $user = $request->user();
        if (!$user) {
            return back()->with('error', 'Please sign in to apply.');
        }

        // 验证表单数据
        $request->validate([
            'experience' => 'required|string|min:10|max:1000',
            'motivation' => 'required|string|min:10|max:500',
        ], [
            'experience.min' => 'Experience must be at least 10 characters long.',
            'experience.max' => 'Experience must not exceed 1000 characters.',
            'motivation.min' => 'Motivation must be at least 10 characters long.',
            'motivation.max' => 'Motivation must not exceed 500 characters.',
        ]);

        try {
            // 检查项目是否已满
            if ($project->isFull()) {
                return back()->with('error', 'This project is full. No more applications are being accepted.');
            }

            // 检查时间冲突（除非用户明确选择忽略）
            if (!$request->has('ignore_conflicts')) {
                $conflictingProjects = $this->checkScheduleConflicts($user, $project);
                if ($conflictingProjects->isNotEmpty()) {
                    $conflictMessage = "You have a schedule conflict with the following project(s):\n";
                    foreach ($conflictingProjects as $conflictProject) {
                        $conflictMessage .= "• {$conflictProject->title} on {$conflictProject->date} at {$conflictProject->time}\n";
                    }
                    $conflictMessage .= "\nDo you want to continue with your application?";
                    
                    return back()->withErrors(['warning' => $conflictMessage]);
                }
            }

            // 检查是否已有有效的申请（pending或approved）
            $existingApplication = ProjectApplication::where('user_id', $user->id)
                ->where('community_project_id', $project->id)
                ->whereIn('status', ['pending','approved'])
                ->first();

            if ($existingApplication) {
                return back()->with('error', 'You have already applied to this project.');
            }

            // 如果之前有rejected或withdrawn的申请，更新它而不是创建新的
            $oldApplication = ProjectApplication::where('user_id', $user->id)
                ->where('community_project_id', $project->id)
                ->whereIn('status', ['rejected','withdrawn'])
                ->first();

            $application = null;
            if ($oldApplication) {
                // 更新现有申请
                $oldApplication->update([
                    'experience' => $request->experience,
                    'motivation' => $request->motivation,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $application = $oldApplication;
            } else {
                // 创建新申请
                $application = ProjectApplication::create([
                    'user_id' => $user->id,
                    'community_project_id' => $project->id,
                    'experience' => $request->experience,
                    'motivation' => $request->motivation,
                    'status' => 'pending',
                ]);
            }

            // 发送通知给用户
            NotificationService::projectApplicationSubmitted($application);
            
            // 发送通知给管理员
            $adminUsers = User::where('role', 'admin')->get();
            foreach ($adminUsers as $admin) {
                NotificationService::send(
                    $admin->id,
                    'project_application_received',
                    'New Project Application',
                    "{$user->name} has applied for the project '{$project->title}'",
                    [
                        'action' => 'review_application',
                        'application_id' => $application->id,
                        'project_id' => $project->id,
                        'project_title' => $project->title,
                        'applicant_name' => $user->name,
                        'applicant_id' => $user->id
                    ]
                );
            }

            // 记录SystemLog
            SystemLog::log(
                'project_application_submitted',
                "Volunteer {$user->name} applied for project: {$project->title}",
                [
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'application_id' => $application->id,
                    'user_role' => $user->role
                ]
            );

            return back()->with('success', 'Application submitted successfully!');
        } catch (\Throwable $e) {
            Log::error('Apply failed', ['pid' => $project->id, 'e' => $e->getMessage()]);
            return back()->with('error', 'Failed to submit application. Please try again.');
        }
    }

    // 撤回申请（Inertia 表单）
    public function withdraw(Request $request, CommunityProject $project)
    {
        $user = $request->user();
        if (!$user) {
            return back()->with('error', 'Please sign in first.');
        }

        try {
            $app = ProjectApplication::where('user_id', $user->id)
                ->where('community_project_id', $project->id)
                ->whereIn('status', ['pending','approved'])
                ->first();

            if (!$app) {
                return back()->with('error', 'No active application found.');
            }

            // 如果之前是approved状态，需要减少项目的volunteer人数
            if ($app->status === 'approved') {
                $project->decrement('volunteers_joined');
            }

            // 标记为 withdrawn
            $app->status = 'withdrawn';
            $app->save();

            // 发送通知给用户
            NotificationService::send(
                $user->id,
                'project_application_withdrawn',
                'Application Withdrawn',
                "You have withdrawn your application for '{$project->title}'",
                [
                    'action' => 'view_projects',
                    'project_id' => $project->id,
                    'project_title' => $project->title
                ]
            );

            // 发送通知给管理员
            $adminUsers = User::where('role', 'admin')->get();
            foreach ($adminUsers as $admin) {
                NotificationService::send(
                    $admin->id,
                    'project_application_withdrawn',
                    'Application Withdrawn',
                    "{$user->name} has withdrawn their application for '{$project->title}'",
                    [
                        'action' => 'view_applications',
                        'application_id' => $app->id,
                        'project_id' => $project->id,
                        'project_title' => $project->title,
                        'applicant_name' => $user->name,
                        'applicant_id' => $user->id
                    ]
                );
            }

            // 记录SystemLog
            SystemLog::log(
                'project_application_withdrawn',
                "Volunteer {$user->name} withdrew application for project: {$project->title}",
                [
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'application_id' => $app->id,
                    'user_role' => $user->role,
                    'previous_status' => $app->status
                ]
            );

            return back()->with('success', 'Application withdrawn.');
        } catch (\Throwable $e) {
            Log::error('Withdraw failed', ['pid' => $project->id, 'uid' => $user->id ?? null, 'e' => $e->getMessage()]);
            return back()->with('error', 'Failed to withdraw application.');
        }
    }

    // 获取用户的申请列表
    public function myApplications()
    {
        $user = auth()->user();
        if (!$user) {
            return back()->with('error', 'Please sign in to view your applications.');
        }

        $applications = ProjectApplication::where('user_id', $user->id)
            ->with(['project' => function ($query) {
                $query->select('id', 'title', 'location', 'date', 'status');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return inertia('Volunteer/MyApplications', [
            'applications' => $applications,
        ]);
    }

    /**
     * Check for schedule conflicts with user's approved projects
     */
    private function checkScheduleConflicts($user, $newProject)
    {
        // Get user's approved projects
        $approvedApplications = ProjectApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->with('project')
            ->get();

        $conflictingProjects = collect();

        foreach ($approvedApplications as $application) {
            $approvedProject = $application->project;
            if ($approvedProject && $newProject->hasScheduleConflictWith($approvedProject)) {
                $conflictingProjects->push($approvedProject);
            }
        }

        return $conflictingProjects;
    }

    // —— 如果你有 JSON 接口（例如收藏/检查名额），统一返回 {success,message}
    public function toggleBookmark(Request $request, CommunityProject $project)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Please sign in.'], 401);
            }

            // 伪代码：按你的收藏逻辑实现
            $bookmarked = false; // TODO: 切换收藏状态，并得到结果
            $msg = $bookmarked ? 'Added to bookmarks.' : 'Removed from bookmarks.';

            return response()->json(['success' => true, 'message' => $msg]);
        } catch (\Throwable $e) {
            Log::error('Toggle bookmark failed', ['pid' => $project->id, 'e' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update bookmark.'], 422);
        }
    }
}
