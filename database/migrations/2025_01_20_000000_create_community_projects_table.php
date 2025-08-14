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
        Schema::create('community_projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('location');
            $table->date('date');
            $table->time('time');
            $table->string('duration');
            $table->integer('volunteers_needed');
            $table->integer('volunteers_joined')->default(0);
            $table->integer('points_reward');
            $table->enum('category', ['search', 'awareness', 'training']);
            $table->enum('status', ['active', 'upcoming', 'completed', 'cancelled'])->default('active');
            $table->json('photo_paths')->nullable(); // Store multiple photo paths
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_projects');
    }
};
