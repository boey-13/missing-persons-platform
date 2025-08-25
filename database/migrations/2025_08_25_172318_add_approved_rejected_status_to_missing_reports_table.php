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
        DB::statement("ALTER TABLE missing_reports MODIFY COLUMN case_status ENUM('Pending', 'Approved', 'Rejected', 'Missing', 'Found', 'Closed') DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert case_status enum to previous state
        DB::statement("ALTER TABLE missing_reports MODIFY COLUMN case_status ENUM('Pending', 'Missing', 'Found', 'Closed') DEFAULT 'Pending'");
    }
};
