<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('sighting_reports')) {
            return; // table already exists (from earlier migration run)
        }
        Schema::create('sighting_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('missing_report_id')->constrained('missing_reports')->cascadeOnDelete();
            $table->string('location');
            $table->text('description')->nullable();
            $table->timestamp('sighted_at')->nullable();
            $table->json('photo_paths')->nullable();
            $table->string('reporter_name');
            $table->string('reporter_phone');
            $table->string('reporter_email')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sighting_reports');
    }
};


