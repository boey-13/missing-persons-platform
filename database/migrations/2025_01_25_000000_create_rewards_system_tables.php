<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reward categories table
        Schema::create('reward_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "E-commerce", "Food & Beverage", "Entertainment"
            $table->string('description')->nullable();
            $table->string('icon')->nullable(); // Icon class or image path
            $table->timestamps();
        });

        // Rewards table
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('reward_categories')->onDelete('cascade');
            $table->string('name'); // e.g., "RM10 Shopee Voucher"
            $table->text('description')->nullable();
            $table->integer('points_required');
            $table->integer('stock_quantity')->default(0); // 0 means unlimited
            $table->integer('redeemed_count')->default(0);
            $table->string('image_path')->nullable(); // Reward image
            $table->string('voucher_code_prefix')->nullable(); // For generating voucher codes
            $table->integer('validity_days')->default(30); // How many days the voucher is valid after redemption
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // User points table (current balance)
        Schema::create('user_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('current_points')->default(0);
            $table->integer('total_earned_points')->default(0);
            $table->integer('total_spent_points')->default(0);
            $table->timestamps();
            
            $table->unique('user_id');
        });

        // Point transactions table (history)
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['earned', 'spent']);
            $table->integer('points');
            $table->string('action'); // e.g., "registration", "missing_report", "sighting_report", "social_share", "community_project", "reward_redemption"
            $table->text('description');
            $table->json('metadata')->nullable(); // Additional data like report_id, project_id, etc.
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });

        // User rewards table (redemption history)
        Schema::create('user_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reward_id')->constrained('rewards')->onDelete('cascade');
            $table->string('voucher_code')->unique(); // Generated unique voucher code
            $table->integer('points_spent');
            $table->timestamp('redeemed_at');
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'used', 'expired'])->default('active');
            $table->timestamp('used_at')->nullable(); // When the voucher was actually used
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });

        // Social share tracking table
        Schema::create('social_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('missing_report_id')->constrained('missing_reports')->onDelete('cascade');
            $table->enum('platform', ['facebook', 'twitter', 'whatsapp', 'telegram', 'instagram']);
            $table->string('share_url')->nullable();
            $table->boolean('points_awarded')->default(false);
            $table->timestamps();
            
            $table->unique(['user_id', 'missing_report_id', 'platform']);
            $table->index(['user_id', 'points_awarded']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_shares');
        Schema::dropIfExists('user_rewards');
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('user_points');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('reward_categories');
    }
};
