<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update case_status enum to include Approved and Rejected
        // Handle different database drivers
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE missing_reports MODIFY COLUMN case_status ENUM('Pending', 'Approved', 'Rejected', 'Missing', 'Found', 'Closed') DEFAULT 'Pending'");
        } else {
            // For SQLite and other databases, we'll use a different approach
            // SQLite doesn't support MODIFY COLUMN, so we'll skip this migration in testing
            // The enum values will be handled at the application level
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert case_status enum to previous state
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE missing_reports MODIFY COLUMN case_status ENUM('Pending', 'Missing', 'Found', 'Closed') DEFAULT 'Pending'");
        }
        // For SQLite, no action needed as we didn't modify the column
    }
};
