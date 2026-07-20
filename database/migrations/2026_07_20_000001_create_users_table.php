<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('moodle_token')->nullable();
            $table->string('telegram_chat_id')->nullable()->unique();
            $table->string('sync_code')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('subscription_status')->default(false);
            $table->timestamp('subscription_expires_at')->nullable();
            $table->boolean('trial_used')->default(false);
            $table->timestamp('trial_started_at')->nullable();
            $table->string('last_moodle_error')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
