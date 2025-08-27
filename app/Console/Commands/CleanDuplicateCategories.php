<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RewardCategory;
use App\Models\Reward;
use Illuminate\Support\Facades\DB;

class CleanDuplicateCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rewards:clean-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean duplicate reward categories and update rewards to use the first occurrence';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to clean duplicate reward categories...');

        // Get all categories grouped by name
        $duplicates = RewardCategory::select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate categories found.');
            return;
        }

        $this->info('Found ' . $duplicates->count() . ' categories with duplicates:');
        $duplicates->each(function ($duplicate) {
            $this->line('- ' . $duplicate->name);
        });

        foreach ($duplicates as $duplicate) {
            $categories = RewardCategory::where('name', $duplicate->name)->orderBy('id')->get();
            $keepCategory = $categories->first(); // Keep the first one
            $deleteCategories = $categories->skip(1); // Delete the rest

            $this->info("Processing category: {$duplicate->name}");
            $this->line("  Keeping category ID: {$keepCategory->id}");

            // Update all rewards that use the duplicate categories to use the kept category
            foreach ($deleteCategories as $deleteCategory) {
                $this->line("  Updating rewards from category ID: {$deleteCategory->id}");
                
                // Update rewards to use the kept category
                Reward::where('category_id', $deleteCategory->id)
                    ->update(['category_id' => $keepCategory->id]);
                
                // Delete the duplicate category
                $deleteCategory->delete();
            }
        }

        $this->info('Duplicate categories cleaned successfully!');
        
        // Show final category count
        $finalCount = RewardCategory::count();
        $this->info("Final category count: {$finalCount}");
    }
}
