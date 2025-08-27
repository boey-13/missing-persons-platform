<?php

namespace App\Http\Controllers;

use App\Models\CommunityProject;
use App\Models\ProjectApplication; // 你的申请模型；如命名不同请改
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
            'experience' => 'required|string|min:10',
            'motivation' => 'required|string|min:10',
        ]);

        try {
            // 防重复：同一用户对同一项目仅一条有效申请
            $exists = ProjectApplication::where('user_id', $user->id)
                ->where('community_project_id', $project->id)
                ->whereIn('status', ['pending','approved'])
                ->exists();

            if ($exists) {
                return back()->with('error', 'You have already applied to this project.');
            }

            ProjectApplication::create([
                'user_id' => $user->id,
                'community_project_id' => $project->id,
                'experience' => $request->experience,
                'motivation' => $request->motivation,
                'status' => 'pending',
            ]);

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

            // 你可以选择删除或标记为 withdrawn
            $app->status = 'withdrawn';
            $app->save();

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
