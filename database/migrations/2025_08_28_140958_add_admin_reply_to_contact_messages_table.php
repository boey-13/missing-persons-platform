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
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->text('admin_reply')->nullable();
            $table->string('admin_reply_subject')->nullable();
            $table->timestamp('admin_replied_at')->nullable();
            $table->unsignedBigInteger('admin_replied_by')->nullable();
            $table->foreign('admin_replied_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropForeign(['admin_replied_by']);
            $table->dropColumn(['admin_reply', 'admin_reply_subject', 'admin_replied_at', 'admin_replied_by']);
        });
    }
};
