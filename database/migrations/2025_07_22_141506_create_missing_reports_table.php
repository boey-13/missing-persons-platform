<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('missing_reports', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('nickname')->nullable();
            $table->integer('age')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->integer('height_cm')->nullable();
            $table->integer('weight_kg')->nullable();
            $table->text('physical_description')->nullable();
            $table->date('last_seen_date')->nullable();
            $table->string('last_seen_location')->nullable();
            $table->text('last_seen_clothing')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('police_report_path')->nullable();
            $table->string('reporter_name');
            $table->string('reporter_relationship')->nullable();
            $table->string('reporter_phone');
            $table->string('reporter_email')->nullable();
            $table->text('additional_notes')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('missing_reports');
    }
};
