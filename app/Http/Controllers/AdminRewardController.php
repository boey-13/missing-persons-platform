<?php

namespace App\Http\Controllers;

use App\Services\RewardService;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminRewardController extends Controller
{
    protected $rewardService;
    protected $pointsService;

    public function __construct(RewardService $rewardService, PointsService $pointsService)
    {
        $this->rewardService = $rewardService;
        $this->pointsService = $pointsService;
    }

    /**
     * Show rewards management page
     */
    public function index(Request $request): Response
    {
        // Check admin permissions
        if (auth()->check() && auth()->user()->role === 'admin') {
            $categoryId = $request->get('category');
            $search = $request->get('search');
            $status = $request->get('status');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $rewards = $this->rewardService->getAllRewardsWithFilters($categoryId, $search, $status, $sortBy, $sortOrder);
            $categories = $this->rewardService->getCategories();
            $stats = $this->rewardService->getRewardStats();

            return Inertia::render('Admin/ManageRewards', [
                'rewards' => $rewards,
                'categories' => $categories,
                'stats' => $stats,
                'filters' => $request->only(['category', 'search', 'status', 'sort_by', 'sort_order']),
            ]);
        }
        
        abort(403, 'Unauthorized. Admin access required.');
    }

    /**
     * Show create reward form
     */
    public function create(): Response
    {
        $categories = $this->rewardService->getCategories();

        return Inertia::render('Admin/CreateReward', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store new reward
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:reward_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'stock_quantity' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'voucher_code_prefix' => 'nullable|string|max:10',
            'validity_days' => 'required|integer|min:1|max:365',
            'status' => 'required|in:active,inactive',
        ]);

        $data = $request->except('image');

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('rewards', 'public');
            $data['image_path'] = $imagePath;
        }

        try {
            $reward = $this->rewardService->createReward($data);

            return redirect()->route('admin.rewards')
                ->with('success', 'Reward created successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show edit reward form
     */
    public function edit($id): Response
    {
        $reward = $this->rewardService->getReward($id);
        $categories = $this->rewardService->getCategories();

        return Inertia::render('Admin/EditReward', [
            'reward' => $reward,
            'categories' => $categories,
        ]);
    }

    /**
     * Update reward
     */
    public function update(Request $request, $id)
    {
        // Check admin permissions
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized. Admin access required.');
        }
        
        $reward = $this->rewardService->getReward($id);

        $request->validate([
            'category_id' => 'required|exists:reward_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'stock_quantity' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'voucher_code_prefix' => 'nullable|string|max:10',
            'validity_days' => 'required|integer|min:1|max:365',
            'status' => 'required|in:active,inactive',
        ]);

        // Handle empty stock_quantity
        $data = $request->except('image');
        if (empty($data['stock_quantity'])) {
            $data['stock_quantity'] = null;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($reward->image_path && file_exists(public_path('storage/' . $reward->image_path))) {
                unlink(public_path('storage/' . $reward->image_path));
            }
            
            $imagePath = $request->file('image')->store('rewards', 'public');
            $data['image_path'] = $imagePath;
        }

        try {
            $this->rewardService->updateReward($reward, $data);

            return redirect()->route('admin.rewards')
                ->with('success', 'Reward updated successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete reward
     */
    public function destroy($id)
    {
        $reward = $this->rewardService->getReward($id);

        try {
            $this->rewardService->deleteReward($reward);

            return redirect()->route('admin.rewards')
                ->with('success', 'Reward deleted successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show categories management
     */
    public function categories(): Response
    {
        $categories = $this->rewardService->getCategories();

        return Inertia::render('Admin/ManageRewardCategories', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store new category
     */
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:reward_categories,name',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
        ]);

        try {
            $this->rewardService->createCategory($request->all());

            return redirect()->route('admin.rewards.categories')
                ->with('success', 'Category created successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update category
     */
    public function updateCategory(Request $request, $id)
    {
        $category = \App\Models\RewardCategory::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:reward_categories,name,' . $id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
        ]);

        try {
            $this->rewardService->updateCategory($category, $request->all());

            return redirect()->route('admin.rewards.categories')
                ->with('success', 'Category updated successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete category
     */
    public function destroyCategory($id)
    {
        $category = \App\Models\RewardCategory::findOrFail($id);

        try {
            $this->rewardService->deleteCategory($category);

            return redirect()->route('admin.rewards.categories')
                ->with('success', 'Category deleted successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show reward statistics
     */
    public function stats(): Response
    {
        $stats = $this->rewardService->getRewardStats();
        $recentRedemptions = \App\Models\UserReward::with(['user', 'reward'])
            ->orderBy('redeemed_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/RewardStats', [
            'stats' => $stats,
            'recentRedemptions' => $recentRedemptions,
        ]);
    }
}
