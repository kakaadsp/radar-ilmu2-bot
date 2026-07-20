<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('moodle_task_id');
            $table->string('task_name');
            $table->string('course_name')->default('');
            $table->string('course_fullname')->default('');
            $table->timestamp('deadline');
            $table->boolean('new_task_sent')->default(false);
            $table->boolean('h_7_sent')->default(false);
            $table->boolean('h_3_sent')->default(false);
            $table->boolean('h_1_sent')->default(false);
            $table->string('moodle_url')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'moodle_task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_reminders');
    }
};
