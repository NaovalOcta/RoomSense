<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->string('pending_email')->nullable();
            $table->string('pending_email_token', 64)->nullable();
            $table->timestamp('pending_email_expires_at')->nullable();
            $table->unsignedBigInteger('auth_session_version')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'profile_photo_path', 'pending_email', 'pending_email_token', 'pending_email_expires_at', 'auth_session_version']);
        });
    }
};
