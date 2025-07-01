<?php

namespace Err0r\Larasub\Models;

use Carbon\Carbon;
use Err0r\Larasub\Traits\HasConfigurableIds;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string|int $subscription_id
 * @property string|int $feature_id
 * @property float $credits
 * @property ?string $reason
 * @property ?string $granted_by_type
 * @property ?string $granted_by_id
 * @property ?Carbon $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Subscription $subscription
 * @property-read Feature $feature
 * @property-read Model $grantedBy
 */
class SubscriptionFeatureCredit extends Model
{
    use HasConfigurableIds;
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'feature_id',
        'credits',
        'reason',
        'granted_by_type',
        'granted_by_id',
        'expires_at',
    ];

    protected $casts = [
        'credits' => 'decimal:4',
        'expires_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('larasub.tables.subscription_feature_credits.name'));
    }

    protected function usesUuids(): bool
    {
        return config('larasub.tables.subscription_feature_credits.uuid');
    }

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        /** @var class-string<Subscription> */
        $class = config('larasub.models.subscription');

        return $this->belongsTo($class);
    }

    /**
     * @return BelongsTo<Feature, $this>
     */
    public function feature(): BelongsTo
    {
        /** @var class-string<Feature> */
        $class = config('larasub.models.feature');

        return $this->belongsTo($class);
    }

    /**
     * Get the entity that granted these credits
     */
    public function grantedBy(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to include only non-expired credits
     *
     * @param Builder<static> $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to include only expired credits
     *
     * @param Builder<static> $query
     * @return Builder<static>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
    }

    /**
     * Scope to filter by feature slug
     *
     * @param Builder<static> $query
     * @param string $slug
     * @return Builder<static>
     */
    public function scopeForFeature(Builder $query, string $slug): Builder
    {
        return $query->whereHas('feature', fn ($q) => $q->where('slug', $slug));
    }

    /**
     * Scope to filter by subscription
     *
     * @param Builder<static> $query
     * @param Subscription|string|int $subscription
     * @return Builder<static>
     */
    public function scopeForSubscription(Builder $query, $subscription): Builder
    {
        $subscriptionId = $subscription instanceof Subscription
            ? $subscription->getKey()
            : $subscription;

        return $query->where('subscription_id', $subscriptionId);
    }

    /**
     * Scope to filter credits granted in a specific period
     *
     * @param Builder<static> $query
     * @param Carbon $from
     * @param Carbon $to
     * @return Builder<static>
     */
    public function scopeGrantedInPeriod(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope to filter by reason
     *
     * @param Builder<static> $query
     * @param string $reason
     * @return Builder<static>
     */
    public function scopeWithReason(Builder $query, string $reason): Builder
    {
        return $query->where('reason', 'like', "%{$reason}%");
    }

    /**
     * Scope to order by oldest first (for consumption priority)
     *
     * @param Builder<static> $query
     * @return Builder<static>
     */
    public function scopeOldestFirst(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Check if the credits are expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at <= now();
    }

    /**
     * Check if the credits are active (not expired)
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Get the remaining days until expiration
     */
    public function daysUntilExpiration(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Err0r\Larasub\Database\Factories\SubscriptionFeatureCreditFactory::new();
    }
}