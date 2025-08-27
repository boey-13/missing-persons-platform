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
        Schema::table('missing_reports', function (Blueprint $table) {
            $table->string('reporter_ic_number')->nullable()->after('reporter_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('missing_reports', function (Blueprint $table) {
            $table->dropColumn('reporter_ic_number');
        });
    }
};
