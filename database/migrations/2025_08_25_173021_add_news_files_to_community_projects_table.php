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
        Schema::table('community_projects', function (Blueprint $table) {
            $table->json('news_files')->nullable()->after('latest_news');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('community_projects', function (Blueprint $table) {
            $table->dropColumn('news_files');
        });
    }
};
