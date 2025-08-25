<?php

namespace App\Services;

use App\Models\User;
use App\Models\Reward;
use App\Models\UserReward;
use App\Models\SocialShare;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RewardService
{
    protected $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /**
     * Get available rewards for a user
     */
    public function getAvailableRewards(User $user, $categoryId = null, $showRedeemableOnly = false, $page = 1)
    {
        $query = Reward::with('category')
            ->where('status', 'active');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $rewards = $query->get();

        // Filter rewards based on availability only (show all active rewards)
        $filteredRewards = $rewards->filter(function ($reward) {
            return $reward->is_available;
        });

        // Ensure image_url is included in the response and add can_redeem flag
        $rewardsWithFlags = $filteredRewards->map(function ($reward) use ($user) {
            $reward->image_url = $reward->image_url;
            $reward->can_redeem = $this->pointsService->hasEnoughPoints($user, $reward->points_required);
            return $reward;
        });

        // Filter by redeemability if requested
        if ($showRedeemableOnly) {
            $rewardsWithFlags = $rewardsWithFlags->filter(function ($reward) {
                return $reward->can_redeem;
            });
        }

        // Paginate results (6 per page)
        $perPage = 6;
        $offset = ($page - 1) * $perPage;
        
        $paginatedRewards = $rewardsWithFlags->slice($offset, $perPage);
        
        return [
            'rewards' => $paginatedRewards,
            'total' => $rewardsWithFlags->count(),
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($rewardsWithFlags->count() / $perPage),
            'has_more_pages' => $page < ceil($rewardsWithFlags->count() / $perPage),
        ];
    }

    /**
     * Get all rewards (for admin)
     */
    public function getAllRewards($categoryId = null)
    {
        $query = Reward::with('category');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $rewards = $query->orderBy('created_at', 'desc')->get();

        // Ensure image_url is included in the response
        return $rewards->map(function ($reward) {
            $reward->image_url = $reward->image_url;
            return $reward;
        });
    }

    /**
     * Redeem a reward
     */
    public function redeemReward(User $user, Reward $reward)
    {
        return DB::transaction(function () use ($user, $reward) {
            // Check if user has enough points
            if (!$this->pointsService->hasEnoughPoints($user, $reward->points_required)) {
                throw new \Exception('Insufficient points');
            }

            // Check if reward is available
            if (!$reward->is_available) {
                throw new \Exception('Reward is not available');
            }

            // Check stock
            if ($reward->stock_quantity > 0 && $reward->redeemed_count >= $reward->stock_quantity) {
                throw new \Exception('Reward is out of stock');
            }

            // Generate voucher code
            $voucherCode = $this->generateVoucherCode($reward);

            // Create user reward record
            $userReward = UserReward::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'voucher_code' => $voucherCode,
                'points_spent' => $reward->points_required,
                'redeemed_at' => now(),
                'expires_at' => now()->addDays($reward->validity_days),
                'status' => 'active',
            ]);

            // Deduct points
            $this->pointsService->deductRewardPoints(
                $user,
                $reward->id,
                $reward->name,
                $reward->points_required
            );

            // Update reward redeemed count
            $reward->increment('redeemed_count');

            return $userReward;
        });
    }

    /**
     * Generate unique voucher code
     */
    protected function generateVoucherCode(Reward $reward): string
    {
        $prefix = $reward->voucher_code_prefix ?: 'FINDME';
        $timestamp = now()->format('YmdHis');
        $random = Str::random(6);
        
        return strtoupper("{$prefix}{$timestamp}{$random}");
    }

    /**
     * Get user's redeemed rewards
     */
    public function getUserRewards(User $user, $status = null)
    {
        $query = UserReward::with('reward.category')
            ->where('user_id', $user->id);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('redeemed_at', 'desc')->get();
    }

    /**
     * Get reward by ID
     */
    public function getReward($id)
    {
        return Reward::with('category')->findOrFail($id);
    }

    /**
     * Create a new reward
     */
    public function createReward(array $data)
    {
        return Reward::create($data);
    }

    /**
     * Update a reward
     */
    public function updateReward(Reward $reward, array $data)
    {
        return $reward->update($data);
    }

    /**
     * Delete a reward
     */
    public function deleteReward(Reward $reward)
    {
        // Check if any users have redeemed this reward
        if ($reward->userRewards()->count() > 0) {
            throw new \Exception('Cannot delete reward that has been redeemed');
        }

        return $reward->delete();
    }

    /**
     * Get reward categories
     */
    public function getCategories()
    {
        return \App\Models\RewardCategory::withCount('rewards')->get();
    }

    /**
     * Create reward category
     */
    public function createCategory(array $data)
    {
        return \App\Models\RewardCategory::create($data);
    }

    /**
     * Update reward category
     */
    public function updateCategory(\App\Models\RewardCategory $category, array $data)
    {
        return $category->update($data);
    }

    /**
     * Delete reward category
     */
    public function deleteCategory(\App\Models\RewardCategory $category)
    {
        // Check if category has rewards
        if ($category->rewards()->count() > 0) {
            throw new \Exception('Cannot delete category that has rewards');
        }

        return $category->delete();
    }

    /**
     * Mark voucher as used
     */
    public function markVoucherAsUsed(UserReward $userReward)
    {
        if ($userReward->status !== 'active') {
            throw new \Exception('Voucher is not active');
        }

        if ($userReward->is_expired) {
            throw new \Exception('Voucher has expired');
        }

        return $userReward->update([
            'status' => 'used',
            'used_at' => now(),
        ]);
    }

    /**
     * Get QR code data for voucher
     */
    public function getVoucherQrCodeData(UserReward $userReward)
    {
        return [
            'qrCodeData' => $userReward->qr_code_data,
            'qrCodeImage' => $userReward->qr_code_image,
            'voucherCode' => $userReward->voucher_code,
            'expiresAt' => $userReward->expires_at,
        ];
    }

    /**
     * Get reward statistics
     */
    public function getRewardStats()
    {
        return [
            'total_rewards' => Reward::count(),
            'active_rewards' => Reward::where('status', 'active')->count(),
            'total_redemptions' => UserReward::count(),
            'active_vouchers' => UserReward::where('status', 'active')->count(),
            'expired_vouchers' => UserReward::where('status', 'expired')->count(),
            'used_vouchers' => UserReward::where('status', 'used')->count(),
        ];
    }
}
