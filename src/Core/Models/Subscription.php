<?php

namespace Err0r\Larasub\Core\Models;

use Carbon\Carbon;
use Err0r\Larasub\Core\Contracts\SubscriptionContract;
use Err0r\Larasub\Core\Events\SubscriptionCancelled;
use Err0r\Larasub\Core\Events\SubscriptionCreated;
use Err0r\Larasub\Core\Events\SubscriptionRenewed;
use Err0r\Larasub\Core\Events\SubscriptionResumed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

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
class Subscription extends Model implements SubscriptionContract
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
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

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be mutated to dates.
     */
    protected $dates = [
        'starts_at',
        'ends_at',
        'cancelled_at',
        'trial_ends_at',
    ];

    /**
     * The default values for attributes.
     */
    protected $attributes = [
        'status' => 'active',
        'metadata' => '{}',
    ];

    /**
     * Available subscription statuses.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::created(function ($subscription) {
            event(new SubscriptionCreated($subscription));
        });
    }

    /**
     * Get the table associated with the model.
     */
    public function getTable()
    {
        return config('larasub.tables.subscriptions', 'subscriptions');
    }

    /**
     * Get the subscriber.
     */
    public function subscriber(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the plan.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Scope to get only active subscriptions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    /**
     * Scope to get cancelled subscriptions.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope to get expired subscriptions.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_EXPIRED)
            ->orWhere(function ($q) {
                $q->where('ends_at', '<', now())
                    ->whereNotNull('ends_at');
            });
    }

    /**
     * Scope to get subscriptions on trial.
     */
    public function scopeOnTrial(Builder $query): Builder
    {
        return $query->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now());
    }

    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the subscription is on trial.
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the subscription has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if the subscription has expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || 
               ($this->ends_at && $this->ends_at->isPast());
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(bool $immediately = false): self
    {
        if ($immediately) {
            $this->status = self::STATUS_CANCELLED;
            $this->ends_at = now();
        } else {
            $this->status = self::STATUS_CANCELLED;
            $this->ends_at = $this->ends_at ?: $this->plan->calculateEndDate($this->starts_at);
        }

        $this->cancelled_at = now();
        $this->save();

        event(new SubscriptionCancelled($this));

        return $this;
    }

    /**
     * Resume a cancelled subscription.
     */
    public function resume(): self
    {
        if (!$this->isCancelled() || $this->isExpired()) {
            return $this;
        }

        $this->status = self::STATUS_ACTIVE;
        $this->cancelled_at = null;
        $this->save();

        event(new SubscriptionResumed($this));

        return $this;
    }

    /**
     * Renew the subscription.
     */
    public function renew(): self
    {
        $this->ends_at = $this->plan->calculateEndDate($this->ends_at ?: now());
        $this->status = self::STATUS_ACTIVE;
        $this->save();

        event(new SubscriptionRenewed($this));

        return $this;
    }

    /**
     * Extend the subscription by a number of days.
     */
    public function extend(int $days): self
    {
        $currentEndDate = $this->ends_at ?: now();
        $this->ends_at = $currentEndDate->addDays($days);
        $this->save();

        return $this;
    }

    /**
     * Check if the subscription is ending soon.
     */
    public function endingSoon(int $days = 7): bool
    {
        if (!$this->ends_at) {
            return false;
        }

        return $this->ends_at->isBetween(now(), now()->addDays($days));
    }

    /**
     * Activate the subscription.
     */
    public function activate(): self
    {
        $this->status = self::STATUS_ACTIVE;
        $this->starts_at = $this->starts_at ?: now();
        $this->save();

        return $this;
    }

    /**
     * Get days until expiry.
     */
    public function daysUntilExpiry(): ?int
    {
        if (!$this->ends_at) {
            return null;
        }

        return now()->diffInDays($this->ends_at, false);
    }

    /**
     * Check if the subscription has a trial.
     */
    public function hasTrial(): bool
    {
        return $this->trial_ends_at !== null;
    }
}