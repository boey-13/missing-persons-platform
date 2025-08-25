<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reward;
use App\Models\RewardCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\AdminRewardController;
use App\Services\RewardService;
use App\Services\PointsService;

class TestRewardEditForm extends Command
{
    protected $signature = 'test:reward-edit-form';
    protected $description = 'Test reward edit form data handling';

    public function handle()
    {
        $this->info('Testing reward edit form data handling...');
        
        // Get a sample reward
        $reward = Reward::with('category')->first();
        if (!$reward) {
            $this->error('No rewards found. Please create a reward first.');
            return 1;
        }
        
        $this->info("Testing with reward: {$reward->name}");
        $this->info("Reward ID: {$reward->id}");
        
        // Test what data is passed to Vue component
        $this->info("\nData passed to Vue component:");
        $this->info("reward.category_id: " . ($reward->category_id ?? 'null') . " (type: " . gettype($reward->category_id) . ")");
        $this->info("reward.name: " . ($reward->name ?? 'null') . " (type: " . gettype($reward->name) . ")");
        $this->info("reward.points_required: " . ($reward->points_required ?? 'null') . " (type: " . gettype($reward->points_required) . ")");
        $this->info("reward.validity_days: " . ($reward->validity_days ?? 'null') . " (type: " . gettype($reward->validity_days) . ")");
        $this->info("reward.status: " . ($reward->status ?? 'null') . " (type: " . gettype($reward->status) . ")");
        
        // Test Vue form initialization simulation (CURRENT VERSION)
        $this->info("\nVue form initialization simulation (CURRENT):");
        $vueFormData = [
            'category_id' => $reward->category_id ? (string)$reward->category_id : '',
            'name' => $reward->name ?? '',
            'description' => $reward->description ?? '',
            'points_required' => $reward->points_required ?? '',  // Keep as number
            'stock_quantity' => $reward->stock_quantity ?? '',    // Keep as number
            'validity_days' => $reward->validity_days ?? '',      // Keep as number
            'status' => $reward->status ?? 'active',
        ];
        
        foreach ($vueFormData as $key => $value) {
            $this->info("form.{$key}: '{$value}' (type: " . gettype($value) . ")");
        }
        
        // Test what would be sent to backend
        $this->info("\nData sent to backend (simulated):");
        $backendData = [
            'category_id' => $vueFormData['category_id'],
            'name' => $vueFormData['name'],
            'description' => $vueFormData['description'],
            'points_required' => $vueFormData['points_required'],
            'stock_quantity' => $vueFormData['stock_quantity'],
            'validity_days' => $vueFormData['validity_days'],
            'status' => $vueFormData['status'],
        ];
        
        foreach ($backendData as $key => $value) {
            $this->info("request.{$key}: '{$value}' (type: " . gettype($value) . ")");
        }
        
        // Test validation with actual validation rules
        $this->info("\nValidation test:");
        $validator = \Validator::make($backendData, [
            'category_id' => 'required|exists:reward_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'stock_quantity' => 'nullable|integer|min:0',
            'validity_days' => 'required|integer|min:1|max:365',
            'status' => 'required|in:active,inactive',
        ]);
        
        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error("- {$error}");
            }
            
            // Show which fields failed
            $this->error("\nFailed fields:");
            foreach ($validator->errors()->keys() as $field) {
                $this->error("- {$field}: '{$backendData[$field]}' (type: " . gettype($backendData[$field]) . ")");
            }
        } else {
            $this->info('✅ Validation passed!');
        }
        
        // Test with empty values
        $this->info("\nTesting with empty values:");
        $emptyData = [
            'category_id' => '',
            'name' => '',
            'description' => '',
            'points_required' => '',
            'stock_quantity' => '',
            'validity_days' => '',
            'status' => '',
        ];
        
        $validator = \Validator::make($emptyData, [
            'category_id' => 'required|exists:reward_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'stock_quantity' => 'nullable|integer|min:0',
            'validity_days' => 'required|integer|min:1|max:365',
            'status' => 'required|in:active,inactive',
        ]);
        
        if ($validator->fails()) {
            $this->error('Validation failed with empty values:');
            foreach ($validator->errors()->all() as $error) {
                $this->error("- {$error}");
            }
        } else {
            $this->info('✅ Validation passed with empty values!');
        }
        
        // Test with null values
        $this->info("\nTesting with null values:");
        $nullData = [
            'category_id' => null,
            'name' => null,
            'description' => null,
            'points_required' => null,
            'stock_quantity' => null,
            'validity_days' => null,
            'status' => null,
        ];
        
        $validator = \Validator::make($nullData, [
            'category_id' => 'required|exists:reward_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'stock_quantity' => 'nullable|integer|min:0',
            'validity_days' => 'required|integer|min:1|max:365',
            'status' => 'required|in:active,inactive',
        ]);
        
        if ($validator->fails()) {
            $this->error('Validation failed with null values:');
            foreach ($validator->errors()->all() as $error) {
                $this->error("- {$error}");
            }
        } else {
            $this->info('✅ Validation passed with null values!');
        }
        
        return 0;
    }
}
