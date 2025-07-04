<?php

namespace Err0r\Larasub\Core\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Simple Subscription Model
 * 
 * @property string $id
 * @property string $plan_id
 * @property string $subscriber_type
 * @property string $subscriber_id
 * @property string $status (active|cancelled|expired|pending)
 * @property \Carbon\Carbon $starts_at
 * @property ?\Carbon\Carbon $ends_at
 * @property ?\Carbon\Carbon $cancelled_at
 * @property ?\Carbon\Carbon $trial_ends_at
 * @property array $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property ?\Carbon\Carbon $deleted_at
 * @property-read Plan $plan
 * @property-read Model $subscriber
 */
class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'plan_id',
        'subscriber_type',
        'subscriber_id',
        'status',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'trial_ends_at',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'pending',
        'metadata' => '{}',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            // Auto-activate if start date is set and in the past
            if ($subscription->starts_at && $subscription->starts_at <= now()) {
                $subscription->status = 'active';
            }
        });

        // Update status based on dates
        static::retrieved(function ($subscription) {
            $subscription->updateStatus();
        });
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        $this->setTable(config('larasub.tables.subscriptions', 'subscriptions'));
        
        if (config('larasub.use_uuid', false)) {
            $this->keyType = 'string';
            $this->incrementing = false;
        }
    }

    /**
     * Get the plan for this subscription
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the subscriber (user, team, etc.)
     */
    public function subscriber(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
                     ->where('starts_at', '<=', now())
                     ->where(function ($q) {
                         $q->whereNull('ends_at')
                           ->orWhere('ends_at', '>', now());
                     });
    }

    /**
     * Scope for cancelled subscriptions
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope for expired subscriptions
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope for pending subscriptions
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for subscriptions on trial
     */
    public function scopeOnTrial(Builder $query): Builder
    {
        return $query->whereNotNull('trial_ends_at')
                     ->where('trial_ends_at', '>', now());
    }

    /**
     * Check if the subscription is active
     */
    public function isActive(): bool
    {
        $this->updateStatus();
        return $this->status === 'active';
    }

    /**
     * Check if the subscription is on trial
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the subscription is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if the subscription is expired
     */
    public function isExpired(): bool
    {
        $this->updateStatus();
        return $this->status === 'expired';
    }

    /**
     * Check if the subscription is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Activate the subscription
     */
    public function activate(Carbon $startsAt = null): bool
    {
        $this->starts_at = $startsAt ?? now();
        
        if (!$this->ends_at && $this->plan) {
            $this->ends_at = $this->plan->calculateEndDate($this->starts_at);
        }
        
        $this->status = 'active';
        
        return $this->save();
    }

    /**
     * Cancel the subscription
     */
    public function cancel(bool $immediately = false): bool
    {
        $this->cancelled_at = now();
        
        if ($immediately) {
            $this->ends_at = now();
            $this->status = 'cancelled';
        } else {
            // Will cancel at period end
            $this->status = 'active';
        }
        
        return $this->save();
    }

    /**
     * Resume a cancelled subscription
     */
    public function resume(): bool
    {
        if (!$this->isCancelled() || $this->isExpired()) {
            return false;
        }
        
        $this->cancelled_at = null;
        $this->status = 'active';
        
        // Extend end date if it's in the past
        if ($this->ends_at && $this->ends_at->isPast()) {
            $this->ends_at = $this->plan->calculateEndDate();
        }
        
        return $this->save();
    }

    /**
     * Renew the subscription for another period
     */
    public function renew(): bool
    {
        if (!$this->plan) {
            return false;
        }
        
        $this->starts_at = $this->ends_at ?? now();
        $this->ends_at = $this->plan->calculateEndDate($this->starts_at);
        $this->status = 'active';
        $this->cancelled_at = null;
        
        return $this->save();
    }

    /**
     * Extend the subscription by a specific period
     */
    public function extend(int $days): bool
    {
        if (!$this->ends_at) {
            return false;
        }
        
        $this->ends_at = $this->ends_at->addDays($days);
        
        return $this->save();
    }

    /**
     * Update subscription status based on dates
     */
    protected function updateStatus(): void
    {
        // Don't update if already cancelled
        if ($this->status === 'cancelled' && $this->cancelled_at) {
            return;
        }
        
        // Check if expired
        if ($this->ends_at && $this->ends_at->isPast()) {
            $this->status = 'expired';
            return;
        }
        
        // Check if should be active
        if ($this->starts_at && $this->starts_at->isPast() && $this->status === 'pending') {
            $this->status = 'active';
        }
    }

    /**
     * Get days remaining in subscription
     */
    public function daysRemaining(): int
    {
        if (!$this->ends_at || !$this->isActive()) {
            return 0;
        }
        
        return max(0, now()->diffInDays($this->ends_at, false));
    }

    /**
     * Check if subscription is ending soon
     */
    public function endingSoon(int $days = 7): bool
    {
        return $this->isActive() && $this->daysRemaining() <= $days;
    }
}