<?php

namespace Err0r\Larasub\Models;

use Carbon\Carbon;
use Err0r\Larasub\Enums\FeatureType;
use Err0r\Larasub\Facades\PlanService;
use Err0r\Larasub\Facades\SubscriptionHelperService;
use Err0r\Larasub\Facades\SubscriptionService;
use Err0r\Larasub\Traits\HasEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasEvent;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'plan_id',
        'start_at',
        'end_at',
        'cancelled_at',
        'renewed_from_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('larasub.tables.subscriptions.name'));
    }

    /**
     * @return BelongsTo<Plan>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(config('larasub.models.plan'));
    }

    public function subscriber(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<SubscriptionFeatureUsage>
     */
    public function featuresUsage(): HasMany
    {
        return $this->hasMany(config('larasub.models.subscription_feature_usages'));
    }

    /**
     * @return HasMany<SubscriptionFeatureUsage>
     */
    public function featureUsage(string $slug): HasMany
    {
        return $this->featuresUsage()->whereHas('feature', fn ($q) => $q->slug($slug));
    }

    /**
     * Get the subscription this was renewed from
     * @return BelongsTo<static>
     */
    public function renewedFrom(): BelongsTo 
    {
        return $this->belongsTo(config('larasub.models.subscription'), 'renewed_from_id');
    }

    /**
     * Get the renewal subscription if this was renewed
     * @return HasOne<static>
     */
    public function renewal(): HasOne
    {
        return $this->hasOne(config('larasub.models.subscription'), 'renewed_from_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('start_at', '<=', now())
            ->where(
                fn ($q) => $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now())
            );
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('start_at')->whereNull('cancelled_at');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->whereNotNull('cancelled_at');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('end_at', '<', now());
    }

    public function scopeFuture(Builder $query): Builder
    {
        return $query->where('start_at', '>', now());
    }

    /**
     * Scope for subscriptions that have been renewed
     */
    public function scopeRenewed(Builder $query): Builder
    {
        return $query->whereHas('renewal');
    }

    /**
     * Scope for subscriptions that haven't been renewed
     */
    public function scopeNotRenewed(Builder $query): Builder
    {
        return $query->whereDoesntHave('renewal');
    }

    /**
     * Scope for subscriptions that are renewals of other subscriptions
     */
    public function scopeIsRenewal(Builder $query): Builder
    {
        return $query->whereNotNull('renewed_from_id');
    }

    /**
     * Scope for original subscriptions (not renewals)
     */
    public function scopeIsOriginal(Builder $query): Builder
    {
        return $query->whereNull('renewed_from_id');
    }

    /**
     * Scope for subscriptions that are due for renewal
     * (active, not renewed, and ending within specified days)
     */
    public function scopeDueForRenewal(Builder $query, int $withinDays = 7): Builder
    {
        return $query
            ->active()
            ->notRenewed()
            ->whereNotNull('end_at')
            ->where('end_at', '<=', now()->addDays($withinDays));
    }

    /**
     * Scope a query to only include subscriptions with a specific plan.
     *
     * @param  Plan|string  $plan  The plan instance or slug.
     */
    public function scopeWherePlan(Builder $query, $plan): Builder
    {
        $plan = match (true) {
            $plan instanceof Plan => $plan,
            default => Plan::slug($plan)->first(),
        };

        return $query->where('plan_id', $plan->id);
    }

    /**
     * Scope a query to exclude a specific plan.
     *
     * @param  Plan|string  $plan  Plan instance or slug
     */
    public function scopeWhereNotPlan(Builder $query, $plan): Builder
    {
        return $query->whereNot(fn ($q) => $q->wherePlan($plan));
    }

    /**
     * Scope a query to only include subscriptions with a specific feature.
     *
     * @param  Feature|string  $feature  The feature instance or slug.
     */
    public function scopeWhereFeature(Builder $query, $feature): Builder
    {
        $feature = match (true) {
            $feature instanceof Feature => $feature,
            default => Feature::slug($feature)->first(),
        };

        return $query->whereHas('plan.features.feature', fn ($q) => $q->where('feature_id', $feature->id));
    }

    /**
     * Scope a query to exclude a specific feature.
     *
     * @param  Feature|string  $feature  The feature instance or slug.
     */
    public function scopeWhereNotFeature(Builder $query, $feature): Builder
    {
        return $query->whereNot(fn ($q) => $q->whereFeature($feature));
    }

    /**
     * Scope a query to only include subscriptions which includes specific features.
     *
     * @param  iterable<string>  $features  The array of feature slugs to include.
     */
    public function scopeWhereFeatures(Builder $query, iterable $features): Builder
    {
        $features = collect($features);
        $query->where(function ($q) use ($features) {
            $features->each(fn ($feature) => $q->whereFeature($feature));
        });

        return $query;
    }

    /**
     * Scope a query to exclude subscriptions which includes specific features.
     *
     * @param  iterable<string>  $features  The array of feature slugs to exclude.
     */
    public function scopeWhereNotFeatures(Builder $query, iterable $features): Builder
    {
        return $query->whereNot(fn ($q) => $q->whereFeatures($features));
    }

    /**
     * Determine if the subscription is active.
     *
     * A subscription is considered active if the `start_at` attribute is less than or equal to the current date and time,
     * and the `end_at` attribute is greater than or equal to the current date and time.
     *
     * @return bool True if the subscription is active, false otherwise.
     */
    public function isActive(): bool
    {
        return ($this->start_at !== null && $this->start_at <= now()) && ($this->end_at === null || $this->end_at >= now());
    }

    /**
     * Check if the subscription is pending.
     *
     * A subscription is considered pending if the `start_at` attribute is null.
     *
     * @return bool True if the subscription is pending, false otherwise.
     */
    public function isPending(): bool
    {
        return $this->start_at === null && ! $this->isCancelled();
    }

    /**
     * Check if the subscription is cancelled.
     *
     * This method determines whether the subscription has been cancelled
     * by checking if the `cancelled_at` attribute is not null.
     *
     * @return bool True if the subscription is cancelled, false otherwise.
     */
    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /**
     * Check if the subscription is expired.
     *
     * This method determines if the subscription has expired by comparing
     * the end date of the subscription with the current date and time.
     *
     * @return bool True if the subscription is expired, false otherwise.
     */
    public function isExpired(): bool
    {
        return $this->end_at !== null && $this->end_at < now();
    }

    /**
     * Determine if the subscription start date is in the future.
     *
     * @return bool True if the subscription start date is in the future, false otherwise.
     */
    public function isFuture(): bool
    {
        return $this->start_at > now();
    }

    /**
     * Check if subscription was renewed
     */
    public function isRenewed(): bool
    {
        return $this->renewal()->exists();
    }

    /**
     * Check if subscription is a renewal
     */
    public function isRenewal(): bool
    {
        return $this->renewed_from_id !== null;
    }

    /**
     * Cancel the subscription.
     *
     * @param  bool|null  $immediately  Whether to cancel the subscription immediately. Defaults to false.
     * @return bool Returns true if the subscription was successfully cancelled, false otherwise.
     */
    public function cancel(?bool $immediately = false): bool
    {
        $this->cancelled_at = now();

        if ($immediately || $this->end_at === null) {
            $this->end_at = $this->cancelled_at;
        }

        return $this->save();
    }

    /**
     * Resume the subscription by setting the start and end dates.
     *
     * @param  Carbon|null  $startAt  The start date of the subscription. If null, the current date and time will be used.
     * @param  Carbon|null  $endAt  The end date of the subscription. If null, it will be calculated based on the plan.
     * @return bool Returns true if the subscription was successfully resumed and saved, false otherwise.
     */
    public function resume(?Carbon $startAt = null, ?Carbon $endAt = null): bool
    {
        $this->cancelled_at = null;
        $this->start_at ??= $startAt ?? now();
        $this->end_at = $endAt ?? PlanService::getPlanEndAt($this->plan, $this->start_at);

        return $this->save();
    }

    /**
     * Create a renewal subscription
     * 
     * @param Carbon|null $startAt Custom start date for renewal
     * @throws \LogicException If subscription already renewed
     */
    public function renew(?Carbon $startAt = null): static
    {
        if ($this->isRenewed()) {
            throw new \LogicException('Subscription has already been renewed');
        }

        $renewal = SubscriptionService::renew($this, $startAt);
        
        $renewal->renewed_from_id = $this->id;
        $renewal->save();

        return $renewal;
    }

    /**
     * Retrieve the first plan feature of the subscription's plan by its slug.
     *
     * @param  string  $slug  The slug of the feature to retrieve.
     * @return PlanFeature|null The first plan feature matching the given slug.
     */
    public function planFeature(string $slug)
    {
        return $this->plan->feature($slug);
    }

    /**
     * Check if the subscription has a specific feature.
     *
     * @param  string  $slug  The slug identifier of the feature.
     * @return bool True if the feature exists in the subscription plan, false otherwise.
     */
    public function hasFeature(string $slug): bool
    {
        return $this->planFeature($slug) !== null;
    }

    /**
     * Check if the subscription has an active feature.
     *
     * This method checks if the subscription has the feature and if it is active.
     *
     * @param  string  $slug  The slug identifier of the feature.
     * @return bool True if the feature is active, false otherwise.
     */
    public function hasActiveFeature(string $slug): bool
    {
        return $this->hasFeature($slug) && $this->isActive();
    }

    /**
     * Calculate the remaining usage for a given feature.
     *
     * @param  string  $slug  The slug identifier of the feature.
     * @return float|null The remaining usage of the feature, or null if not applicable.
     *
     * @throws \InvalidArgumentException If the feature is not part of the plan, is non-consumable, or has no value.
     */
    public function remainingFeatureUsage(string $slug): ?float
    {
        /** @var PlanFeature|null */
        $planFeature = $this->planFeature($slug);

        if ($planFeature === null) {
            throw new \InvalidArgumentException("The feature '$slug' is not part of the plan");
        }

        if ($planFeature->feature->type == FeatureType::NON_CONSUMABLE || $planFeature->value === null) {
            throw new \InvalidArgumentException("The feature '$slug' is not consumable or has no value");
        }

        if ($planFeature->isUnlimited()) {
            return floatval(INF);
        }

        $featureUsage = SubscriptionHelperService::totalFeatureUsageInPeriod($this, $slug);

        return $planFeature->value - $featureUsage;
    }

    /**
     * Get the next time a feature will be available for use
     *
     * @param  string  $slug  The feature slug to check
     * @return \Carbon\Carbon|bool|null
     *
     * @throws \InvalidArgumentException
     *
     * @see \Err0r\Larasub\Services\SubscriptionHelperService::nextAvailableFeatureUsageInPeriod()
     */
    public function nextAvailableFeatureUsage(string $slug)
    {
        return SubscriptionHelperService::nextAvailableFeatureUsageInPeriod($this, $slug);
    }

    /**
     * Determine if a feature can be used based on its slug and usage value.
     *
     * This method checks if the subscription is active, validates the usage value,
     * and verifies if the feature is part of the plan and is consumable. It then
     * checks if the remaining feature usage is sufficient for the requested value.
     *
     * @param  string  $slug  The slug identifier of the feature.
     * @param  float  $value  The usage value to check.
     * @return bool True if the feature can be used, false otherwise.
     *
     * @throws \InvalidArgumentException If the usage value is less than or equal to 0,
     *                                   or if the feature is not part of the plan.
     */
    public function canUseFeature(string $slug, float $value): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($value <= 0) {
            throw new \InvalidArgumentException('Usage value must be greater than 0');
        }

        /** @var PlanFeature|null */
        $planFeature = $this->planFeature($slug);

        if ($planFeature === null) {
            throw new \InvalidArgumentException("The feature '$slug' is not part of the plan");
        }

        if ($planFeature->feature->type == FeatureType::NON_CONSUMABLE) {
            return false;
        }

        return $this->remainingFeatureUsage($slug) >= $value;
    }

    /**
     * Use a feature of the subscription.
     *
     * @param  string  $slug  The slug identifier of the feature.
     * @param  float  $value  The value to be used for the feature.
     * @return SubscriptionFeatureUsage The usage record of the feature.
     *
     * @throws \InvalidArgumentException If the feature cannot be used.
     */
    public function useFeature(string $slug, float $value)
    {
        if (! $this->canUseFeature($slug, $value)) {
            throw new \InvalidArgumentException("The feature '$slug' cannot be used");
        }

        /** @var PlanFeature */
        $planFeature = $this->planFeature($slug);

        /** @var SubscriptionFeatureUsage */
        $featureUsage = $this->featuresUsage()->create([
            'feature_id' => $planFeature->feature->id,
            'value' => $value,
        ]);

        return $featureUsage;
    }
}
