<?php

namespace App\Http\Controllers;

use App\Services\RewardService;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RewardController extends Controller
{
    protected $rewardService;
    protected $pointsService;

    public function __construct(RewardService $rewardService, PointsService $pointsService)
    {
        $this->rewardService = $rewardService;
        $this->pointsService = $pointsService;
    }

    /**
     * Show My Rewards page
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $categoryId = $request->get('category');
        $showRedeemableOnly = $request->get('redeemable_only', false);
        $page = $request->get('page', 1);
        
        $rewardsData = $this->rewardService->getAvailableRewards($user, $categoryId, $showRedeemableOnly, $page);
        $categories = $this->rewardService->getCategories();
        $currentPoints = $this->pointsService->getCurrentPoints($user);

        return Inertia::render('Rewards/Index', [
            'rewards' => $rewardsData['rewards'],
            'pagination' => [
                'total' => $rewardsData['total'],
                'per_page' => $rewardsData['per_page'],
                'current_page' => $rewardsData['current_page'],
                'last_page' => $rewardsData['last_page'],
                'has_more_pages' => $rewardsData['has_more_pages'],
            ],
            'categories' => $categories,
            'currentPoints' => $currentPoints,
            'selectedCategory' => $categoryId,
            'showRedeemableOnly' => $showRedeemableOnly,
        ]);
    }

    /**
     * Show user's redeemed vouchers
     */
    public function myVouchers(Request $request): Response
    {
        $user = $request->user();
        $status = $request->get('status');
        $page = $request->get('page', 1);
        
        $vouchersData = $this->rewardService->getUserRewards($user, $status, $page);
        $currentPoints = $this->pointsService->getCurrentPoints($user);

        return Inertia::render('Rewards/MyVouchers', [
            'vouchers' => $vouchersData->items(),
            'pagination' => [
                'total' => $vouchersData->total(),
                'per_page' => $vouchersData->perPage(),
                'current_page' => $vouchersData->currentPage(),
                'last_page' => $vouchersData->lastPage(),
                'has_more_pages' => $vouchersData->hasMorePages(),
            ],
            'currentPoints' => $currentPoints,
            'selectedStatus' => $status,
        ]);
    }

    /**
     * Show reward details modal
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $reward = $this->rewardService->getReward($id);
        $currentPoints = $this->pointsService->getCurrentPoints($user);

        return response()->json([
            'reward' => $reward,
            'currentPoints' => $currentPoints,
            'canRedeem' => $this->pointsService->hasEnoughPoints($user, $reward->points_required),
        ]);
    }

    /**
     * Redeem a reward
     */
    public function redeem(Request $request, $id)
    {
        $user = $request->user();
        $reward = $this->rewardService->getReward($id);

        try {
            $userReward = $this->rewardService->redeemReward($user, $reward);
            
            return response()->json([
                'success' => true,
                'message' => 'Reward redeemed successfully!',
                'userReward' => $userReward,
                'newPointsBalance' => $this->pointsService->getCurrentPoints($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get points history
     */
    public function pointsHistory(Request $request)
    {
        $user = $request->user();
        $history = $this->pointsService->getPointsHistory($user);

        return response()->json([
            'history' => $history,
            'currentPoints' => $this->pointsService->getCurrentPoints($user),
        ]);
    }

    /**
     * Record social share and award points
     */
    public function recordSocialShare(Request $request)
    {
        $request->validate([
            'report_id' => 'required|exists:missing_reports,id',
            'platform' => 'required|in:facebook,twitter,whatsapp,telegram,instagram',
        ]);

        $user = $request->user();
        $reportId = $request->input('report_id');
        $platform = $request->input('platform');

        try {
            $awarded = $this->pointsService->awardSocialSharePoints($user, $reportId, $platform);
            
            if ($awarded) {
                return response()->json([
                    'success' => true,
                    'message' => 'Points awarded for sharing!',
                    'pointsAwarded' => 1,
                    'newPointsBalance' => $this->pointsService->getCurrentPoints($user),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Points already awarded for this share.',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to award points: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get voucher QR code data
     */
    public function getVoucherQrCode(Request $request, $voucherId)
    {
        $user = $request->user();
        $userReward = \App\Models\UserReward::where('id', $voucherId)
            ->where('user_id', $user->id)
            ->with('reward')
            ->firstOrFail();

        $qrCodeData = $this->rewardService->getVoucherQrCodeData($userReward);

        return response()->json($qrCodeData);
    }
}
