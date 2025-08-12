<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('volunteer_applications')) {
            Schema::create('volunteer_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->text('motivation');
                $table->json('skills')->nullable();
                $table->json('languages')->nullable();
                $table->json('availability')->nullable();
                $table->json('preferred_roles')->nullable();
                $table->text('areas')->nullable();
                $table->string('transport_mode')->nullable();
                $table->string('emergency_contact_name');
                $table->string('emergency_contact_phone');
                $table->text('prior_experience')->nullable();
                $table->json('supporting_documents')->nullable();
                $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
                $table->text('status_reason')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Do not drop table in ensure migration
    }
};


