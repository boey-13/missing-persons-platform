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
        Schema::create('project_news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_project_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->json('files')->nullable(); // Store file information
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_news');
    }
};
