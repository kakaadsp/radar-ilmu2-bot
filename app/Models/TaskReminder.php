<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskReminder extends Model
{
    protected $fillable = [
        'user_id',
        'moodle_task_id',
        'task_name',
        'course_name',
        'course_fullname',
        'deadline',
        'new_task_sent',
        'h_7_sent',
        'h_3_sent',
        'h_1_sent',
        'moodle_url',
    ];

    protected $casts = [
        'deadline'      => 'datetime',
        'new_task_sent' => 'boolean',
        'h_7_sent'      => 'boolean',
        'h_3_sent'      => 'boolean',
        'h_1_sent'      => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Days remaining until deadline (negative = overdue).
     */
    public function getDaysRemaining(): int
    {
        return (int) now()->diffInDays($this->deadline, false);
    }
}
