<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('missing_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('full_name');
            $table->string('ic_number')->nullable(); // new
            $table->string('nickname')->nullable();
            $table->integer('age')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->integer('height_cm')->nullable();
            $table->integer('weight_kg')->nullable();
            $table->text('physical_description')->nullable();
            $table->date('last_seen_date')->nullable();
            $table->string('last_seen_location')->nullable();
            $table->text('last_seen_clothing')->nullable();
            $table->json('photo_paths')->nullable(); 
            $table->string('police_report_path')->nullable();
            $table->enum('reporter_relationship', [
                'Parent', 'Sibling', 'Spouse', 'Relative', 'Friend', 'Employer', 'Colleague', 'Neighbor', 'Other'
            ])->nullable();
            $table->string('reporter_relationship_other')->nullable();
            $table->string('reporter_name');
            $table->string('reporter_phone');
            $table->string('reporter_email')->nullable();
            $table->text('additional_notes')->nullable();
            $table->enum('case_status', ['Pending', 'Approved', 'Rejected', 'Missing', 'Found', 'Closed'])->default('Pending'); // new
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('missing_reports');
    }
};
