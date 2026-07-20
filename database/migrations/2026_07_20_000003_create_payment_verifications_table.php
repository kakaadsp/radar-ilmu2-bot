<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('telegram_file_id')->nullable();
            $table->string('telegram_message_id')->nullable();
            $table->integer('amount')->default(5000);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_verifications');
    }
};
