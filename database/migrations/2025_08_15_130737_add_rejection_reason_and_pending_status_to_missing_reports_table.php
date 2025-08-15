<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Added this import for DB facade

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('missing_reports', function (Blueprint $table) {
            // Add rejection_reason field
            $table->text('rejection_reason')->nullable()->after('case_status');
        });

        // Update case_status enum to include Pending
        DB::statement("ALTER TABLE missing_reports MODIFY COLUMN case_status ENUM('Pending', 'Missing', 'Found', 'Closed') DEFAULT 'Pending'");
        
        // Update existing records to have Pending status if they don't have a status
        DB::statement("UPDATE missing_reports SET case_status = 'Pending' WHERE case_status = 'Missing'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('missing_reports', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });

        // Revert case_status enum
        DB::statement("ALTER TABLE missing_reports MODIFY COLUMN case_status ENUM('Missing', 'Found', 'Closed') DEFAULT 'Missing'");
    }
};
