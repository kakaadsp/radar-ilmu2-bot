<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    protected $fillable = [
        'name',
        'email',
        'moodle_token',
        'telegram_chat_id',
        'sync_code',
        'is_active',
        'subscription_status',
        'subscription_expires_at',
        'trial_used',
        'trial_started_at',
        'last_moodle_error',
        'last_checked_at',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'subscription_status'     => 'boolean',
        'trial_used'              => 'boolean',
        'subscription_expires_at' => 'datetime',
        'trial_started_at'        => 'datetime',
        'last_checked_at'         => 'datetime',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function taskReminders(): HasMany
    {
        return $this->hasMany(TaskReminder::class);
    }

    public function paymentVerifications(): HasMany
    {
        return $this->hasMany(PaymentVerification::class);
    }

    // ─── Accessors & Helpers ──────────────────────────────────────────────────

    /**
     * Check if this user has an active subscription or valid free trial.
     */
    public function hasActiveAccess(): bool
    {
        // Active paid subscription
        if ($this->subscription_status && $this->subscription_expires_at && $this->subscription_expires_at->isFuture()) {
            return true;
        }

        // Active free trial (7 days from registration)
        if (!$this->trial_used && $this->trial_started_at) {
            $trialDays = (int) config('app.trial_days', 7);
            return $this->trial_started_at->addDays($trialDays)->isFuture();
        }

        // Trial auto-started on registration
        if (!$this->trial_used && !$this->trial_started_at) {
            return true; // trial hasn't started yet, allow access
        }

        return false;
    }

    /**
     * Check if user has Telegram linked.
     */
    public function hasTelegramLinked(): bool
    {
        return !empty($this->telegram_chat_id);
    }

    /**
     * Check if Moodle token is set.
     */
    public function hasMoodleToken(): bool
    {
        return !empty($this->moodle_token);
    }

    /**
     * Get trial expiry date for display.
     */
    public function getTrialExpiresAt(): ?\Carbon\Carbon
    {
        if ($this->trial_started_at) {
            return $this->trial_started_at->addDays((int) config('app.trial_days', 7));
        }
        return null;
    }

    /**
     * Scope to get only users who should receive notifications.
     */
    public function scopeEligible($query)
    {
        return $query
            ->where('is_active', true)
            ->whereNotNull('telegram_chat_id')
            ->whereNotNull('moodle_token')
            ->where(function ($q) {
                // Either has active paid sub
                $q->where(function ($sq) {
                    $sq->where('subscription_status', true)
                       ->where('subscription_expires_at', '>=', now());
                })
                // Or is in trial period
                ->orWhere(function ($sq) {
                    $trialDays = (int) config('app.trial_days', 7);
                    $sq->where('trial_used', false)
                       ->where('trial_started_at', '>=', now()->subDays($trialDays));
                });
            });
    }
}
