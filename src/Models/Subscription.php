<?php

namespace Err0r\Larasub\Models;

use Carbon\Carbon;
use Err0r\Larasub\Enums\FeatureValue;
use Err0r\Larasub\Facades\PlanService;
use Err0r\Larasub\Facades\SubscriptionHelperService;
use Err0r\Larasub\Traits\HasConfigurableIds;
use Err0r\Larasub\Traits\HasEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|int $plan_version_id
 * @property string|int|null $renewed_from_id
 * @property ?Carbon $start_at
 * @property ?Carbon $end_at
 * @property ?Carbon $cancelled_at
 * @property ?Carbon $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read PlanVersion $planVersion
 * @property-read Model $subscriber
 * @property-read Subscription $renewedFrom
 * @property-read Subscription $renewal
 * @property-read SubscriptionFeatureUsage[] $featuresUsage
 * @property-read SubscriptionFeatureUsage[] $featureUsage
 */
class Subscription extends Model
{
    use HasConfigurableIds;
    use HasEvent;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'plan_version_id',
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

    protected function usesUuids(): bool
    {
        return config('larasub.tables.subscriptions.uuid');
    }

    /**
     * @return BelongsTo<PlanVersion, $this>
     */
    public function planVersion(): BelongsTo
    {
        /** @var class-string<PlanVersion> */
        $class = config('larasub.models.plan_version');

        return $this->belongsTo($class);
    }

    public function subscriber(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<SubscriptionFeatureUsage, $this>
     */
    public function featuresUsage(): HasMany
    {
        /** @var class-string<SubscriptionFeatureUsage> */
        $class = config('larasub.models.subscription_feature_usages');

        return $this->hasMany($class);
    }

    /**
     * @return HasMany<SubscriptionFeatureCredit, $this>
     */
    public function extraCredits(): HasMany
    {
        /** @var class-string<SubscriptionFeatureCredit> */
        $class = config('larasub.models.subscription_feature_credits');

        return $this->hasMany($class);
    }

    /**
     * @return HasMany<SubscriptionFeatureUsage, $this>
     */
    public function featureUsage(string $slug): HasMany
    {
        /** @var HasMany<SubscriptionFeatureUsage, $this> */
        return $this
            ->featuresUsage()
            ->whereHas('feature', fn ($q) => $q->slug($slug));
    }

    /**
     * Get the subscription this was renewed from
     *
     * @return BelongsTo<Subscription, $this>
     */
    public function renewedFrom(): BelongsTo
    {
        /** @var class-string<Subscription> */
        $class = config('larasub.models.subscription');

        return $this->belongsTo($class, 'renewed_from_id');
    }

    /**
     * Get the renewal subscription if this was renewed
     *
     * @return HasOne<Subscription, $this>
     */
    public function renewal(): HasOne
    {
        /** @var class-string<Subscription> */
        $class = config('larasub.models.subscription');

        return $this->hasOne($class, 'renewed_from_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('start_at', '<=', now())
            ->where(
                fn ($q) => $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now())
            );
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('start_at')->whereNull('cancelled_at');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->whereNotNull('cancelled_at');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('end_at', '<', now());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFuture(Builder $query): Builder
    {
        return $query->where('start_at', '>', now());
    }

    /**
     * Scope for subscriptions that have been renewed
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeRenewed(Builder $query): Builder
    {
        return $query->whereHas('renewal');
    }

    /**
     * Scope for subscriptions that haven't been renewed
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeNotRenewed(Builder $query): Builder
    {
        return $query->whereDoesntHave('renewal');
    }

    /**
     * Scope for subscriptions that are due for renewal
     * (active, not renewed, and ending within specified days)
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
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
     * @param  Builder<static>  $query
     * @param  Plan|string  $plan  The plan instance or slug.
     * @return Builder<static>
     */
    public function scopeWherePlan(Builder $query, $plan): Builder
    {
        $plan = match (true) {
            $plan instanceof Plan => $plan,
            default => Plan::slug($plan)->first(),
        };

        return $query->whereHas('planVersion', fn ($q) => $q->where('plan_id', $plan->id));
    }

    /**
     * Scope a query to exclude a specific plan.
     *
     * @param  Builder<static>  $query
     * @param  Plan|string  $plan  Plan instance or slug
     * @return Builder<static>
     */
    public function scopeWhereNotPlan(Builder $query, $plan): Builder
    {
        return $query->whereNot(fn ($q) => $q->wherePlan($plan));
    }

    /**
     * Scope a query to only include subscriptions with a specific plan version.
     *
     * @param  Builder<static>  $query
     * @param  PlanVersion|string  $planVersion  The plan version instance or ID.
     * @return Builder<static>
     */
    public function scopeWherePlanVersion(Builder $query, $planVersion): Builder
    {
        $planVersionId = $planVersion instanceof PlanVersion
            ? $planVersion->getKey()
            : $planVersion;

        return $query->where('plan_version_id', $planVersionId);
    }

    /**
     * Scope a query to exclude a specific plan version.
     *
     * @param  Builder<static>  $query
     * @param  PlanVersion|string  $planVersion  Plan version instance or ID
     * @return Builder<static>
     */
    public function scopeWhereNotPlanVersion(Builder $query, $planVersion): Builder
    {
        return $query->whereNot(fn ($q) => $q->wherePlanVersion($planVersion));
    }

    /**
     * Scope a query to only include subscriptions with a specific feature.
     *
     * @param  Builder<static>  $query
     * @param  Feature|string  $feature  The feature instance or slug.
     * @return Builder<static>
     */
    public function scopeWhereFeature(Builder $query, $feature): Builder
    {
        $feature = match (true) {
            $feature instanceof Feature => $feature,
            default => Feature::slug($feature)->first(),
        };

        return $query->whereHas('planVersion.features.feature', fn ($q) => $q->where('feature_id', $feature->id));
    }

    /**
     * Scope a query to exclude a specific feature.
     *
     * @param  Builder<static>  $query
     * @param  Feature|string  $feature  The feature instance or slug.
     * @return Builder<static>
     */
    public function scopeWhereNotFeature(Builder $query, $feature): Builder
    {
        return $query->whereNot(fn ($q) => $q->whereFeature($feature));
    }

    /**
     * Scope a query to only include subscriptions which includes specific features.
     *
     * @param  Builder<static>  $query
     * @param  iterable<string>  $features  The array of feature slugs to include.
     * @return Builder<static>
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
     * @param  Builder<static>  $query
     * @param  iterable<string>  $features  The array of feature slugs to exclude.
     * @return Builder<static>
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

    public function hasStatusTransitioned(): bool
    {
        return $this->wasJustActivated() || $this->wasJustCancelled() || $this->wasJustResumed() || $this->wasJustRenewed();
    }

    public function wasJustActivated(): bool
    {
        return $this->getOriginal('start_at') === null && $this->start_at !== null;
    }

    public function wasJustCancelled(): bool
    {
        return $this->getOriginal('cancelled_at') === null && $this->cancelled_at !== null;
    }

    public function wasJustResumed(): bool
    {
        return $this->getOriginal('cancelled_at') !== null && $this->cancelled_at === null;
    }

    public function wasJustRenewed(): bool
    {
        return $this->getOriginal('renewed_from_id') === null && $this->renewed_from_id !== null;
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
        $this->end_at = $endAt ?? PlanService::getPlanEndAt($this->planVersion, $this->start_at);

        return $this->save();
    }

    /**
     * Create a renewal subscription
     *
     * @param  Carbon|null  $startAt  Custom start date for renewal
     *
     * @throws \LogicException If subscription already renewed
     */
    public function renew(?Carbon $startAt = null): Subscription
    {
        if ($this->isRenewed()) {
            throw new \LogicException('Subscription has already been renewed');
        }

        $renewal = SubscriptionHelperService::renew($this, $startAt);

        $renewal->renewed_from_id = $this->getKey();
        $renewal->save();

        return $renewal;
    }

    /**
     * Retrieve the first plan feature of the subscription's plan version by its slug.
     *
     * @param  string  $slug  The slug of the feature to retrieve.
     * @return PlanFeature|null The first plan feature matching the given slug.
     */
    public function planFeature(string $slug)
    {
        return $this->planVersion->feature($slug);
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
     * @param  bool  $includeExtraCredits  Whether to include extra credits in calculation
     * @return float|FeatureValue The remaining usage of the feature.
     *
     * @throws \InvalidArgumentException If the feature is not part of the plan, is non-consumable, or has no value.
     */
    public function remainingFeatureUsage(string $slug, bool $includeExtraCredits = true): float|FeatureValue
    {
        if ($includeExtraCredits) {
            return $this->remainingFeatureUsageWithCredits($slug);
        }

        /** @var PlanFeature|null */
        $planFeature = $this->planFeature($slug);

        if ($planFeature === null) {
            throw new \InvalidArgumentException("The feature '$slug' is not part of the plan");
        }

        if ($planFeature->feature->isNonConsumable() || $planFeature->value === null) {
            throw new \InvalidArgumentException("The feature '$slug' is not consumable or has no value");
        }

        if ($planFeature->isUnlimited()) {
            return FeatureValue::UNLIMITED;
        }

        $planFeatureValue = floatval($planFeature->value);
        $featureUsage = $this->totalFeatureUsageInPeriod($slug);

        return $planFeatureValue - $featureUsage;
    }

    /**
     * Get the next time a feature will be available for use
     *
     * @param  string  $slug  The feature slug to check
     *
     * @throws \InvalidArgumentException
     *
     * @see \Err0r\Larasub\Services\SubscriptionHelperService::nextAvailableFeatureUsageInPeriod()
     */
    public function nextAvailableFeatureUsage(string $slug): bool|Carbon|null
    {
        return SubscriptionHelperService::nextAvailableFeatureUsageInPeriod($this, $slug);
    }

    /**
     * Get the total usage of a feature in the current period.
     *
     * @param  string  $slug  The slug identifier of the feature.
     * @return float The total usage of the feature in the current period.
     */
    public function totalFeatureUsageInPeriod(string $slug): float
    {
        return SubscriptionHelperService::totalFeatureUsageInPeriod($this, $slug);
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
     * @param  bool  $includeExtraCredits  Whether to include extra credits in calculation
     * @return bool True if the feature can be used, false otherwise.
     *
     * @throws \InvalidArgumentException If the usage value is less than or equal to 0,
     *                                   or if the feature is not part of the plan.
     */
    public function canUseFeature(string $slug, float $value, bool $includeExtraCredits = true): bool
    {
        if ($includeExtraCredits) {
            return $this->canUseFeatureWithCredits($slug, $value);
        }

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

        if ($planFeature->feature->isNonConsumable()) {
            return false;
        }

        $remainingUsage = $this->remainingFeatureUsage($slug, false);

        if ($remainingUsage === FeatureValue::UNLIMITED) {
            return true;
        }

        return $remainingUsage >= $value;
    }

    /**
     * Use a feature of the subscription.
     *
     * @param  string  $slug  The slug identifier of the feature.
     * @param  float  $value  The value to be used for the feature.
     * @param  bool  $useExtraCredits  Whether to consume extra credits first
     * @return SubscriptionFeatureUsage The usage record of the feature.
     *
     * @throws \InvalidArgumentException If the feature cannot be used.
     */
    public function useFeature(string $slug, float $value, bool $useExtraCredits = true)
    {
        if ($useExtraCredits) {
            return $this->useFeatureWithCredits($slug, $value);
        }

        if (! $this->canUseFeature($slug, $value, false)) {
            throw new \InvalidArgumentException("The feature '$slug' cannot be used");
        }

        /** @var PlanFeature */
        $planFeature = $this->planFeature($slug);

        /** @var SubscriptionFeatureUsage */
        $featureUsage = $this->featuresUsage()->create([
            'feature_id' => $planFeature->feature->getKey(),
            'value' => $value,
        ]);

        return $featureUsage;
    }

    /**
     * Grant extra credits for a specific feature
     *
     * @param string $slug Feature slug
     * @param float $credits Number of credits to grant
     * @param array $options Additional options (reason, granted_by, expires_at)
     * @return SubscriptionFeatureCredit
     */
    public function grantExtraCredits(string $slug, float $credits, array $options = []): SubscriptionFeatureCredit
    {
        if ($credits <= 0) {
            throw new \InvalidArgumentException('Credits must be greater than 0');
        }

        $feature = Feature::slug($slug)->first();
        if (!$feature) {
            throw new \InvalidArgumentException("Feature '{$slug}' not found");
        }

        if ($feature->isNonConsumable()) {
            throw new \InvalidArgumentException("Feature '{$slug}' is non-consumable and cannot have credits");
        }

        $data = [
            'feature_id' => $feature->getKey(),
            'credits' => $credits,
            'reason' => $options['reason'] ?? null,
            'expires_at' => $options['expires_at'] ?? null,
        ];

        if (isset($options['granted_by'])) {
            $data['granted_by_type'] = get_class($options['granted_by']);
            $data['granted_by_id'] = $options['granted_by']->getKey();
        }

        return $this->extraCredits()->create($data);
    }

    /**
     * Get total extra credits for a feature (active only)
     *
     * @param string $slug Feature slug
     * @return float
     */
    public function totalExtraCredits(string $slug): float
    {
        return $this->extraCredits()
            ->forFeature($slug)
            ->active()
            ->sum('credits');
    }

    /**
     * Get extra credits for a feature with query builder
     *
     * @param string $slug Feature slug
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function extraCreditsForFeature(string $slug)
    {
        return $this->extraCredits()->forFeature($slug);
    }

    /**
     * Enhanced remaining feature usage calculation including extra credits
     *
     * @param string $slug Feature slug
     * @return float|FeatureValue
     */
    public function remainingFeatureUsageWithCredits(string $slug): float|FeatureValue
    {
        /** @var PlanFeature|null */
        $planFeature = $this->planFeature($slug);

        if ($planFeature === null) {
            throw new \InvalidArgumentException("The feature '$slug' is not part of the plan");
        }

        if ($planFeature->feature->isNonConsumable() || $planFeature->value === null) {
            throw new \InvalidArgumentException("The feature '$slug' is not consumable or has no value");
        }

        if ($planFeature->isUnlimited()) {
            return FeatureValue::UNLIMITED;
        }

        $planFeatureValue = floatval($planFeature->value);
        $extraCredits = $this->totalExtraCredits($slug);
        $totalAvailable = $planFeatureValue + $extraCredits;
        
        $featureUsage = $this->totalFeatureUsageInPeriod($slug);

        return $totalAvailable - $featureUsage;
    }

    /**
     * Enhanced can use feature check including extra credits
     *
     * @param string $slug Feature slug
     * @param float $value Usage value
     * @return bool
     */
    public function canUseFeatureWithCredits(string $slug, float $value): bool
    {
        if (!$this->isActive()) {
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

        if ($planFeature->feature->isNonConsumable()) {
            return false;
        }

        $remainingUsage = $this->remainingFeatureUsageWithCredits($slug);

        if ($remainingUsage === FeatureValue::UNLIMITED) {
            return true;
        }

        return $remainingUsage >= $value;
    }

    /**
     * Use feature with credit consumption priority (extra credits first)
     *
     * @param string $slug Feature slug
     * @param float $value Usage value
     * @return SubscriptionFeatureUsage
     */
    public function useFeatureWithCredits(string $slug, float $value)
    {
        if (!$this->canUseFeatureWithCredits($slug, $value)) {
            throw new \InvalidArgumentException("The feature '$slug' cannot be used");
        }

        // First, consume extra credits if available
        $this->consumeExtraCredits($slug, $value);

        // Record the usage as normal
        /** @var PlanFeature */
        $planFeature = $this->planFeature($slug);

        /** @var SubscriptionFeatureUsage */
        $featureUsage = $this->featuresUsage()->create([
            'feature_id' => $planFeature->feature->getKey(),
            'value' => $value,
        ]);

        return $featureUsage;
    }

    /**
     * Consume extra credits for a feature (oldest first)
     *
     * @param string $slug Feature slug
     * @param float $value Usage value
     * @return float Remaining value after consuming credits
     */
    protected function consumeExtraCredits(string $slug, float $value): float
    {
        $remainingValue = $value;
        
        $credits = $this->extraCredits()
            ->forFeature($slug)
            ->active()
            ->oldestFirst()
            ->get();

        foreach ($credits as $credit) {
            if ($remainingValue <= 0) {
                break;
            }

            $consumeAmount = min($credit->credits, $remainingValue);
            
            $credit->credits -= $consumeAmount;
            $remainingValue -= $consumeAmount;

            if ($credit->credits <= 0) {
                $credit->delete();
            } else {
                $credit->save();
            }
        }

        return $remainingValue;
    }

    /**
     * Get credit usage statistics for a feature
     *
     * @param string $slug Feature slug
     * @return array
     */
    public function getCreditUsageStats(string $slug): array
    {
        $planFeature = $this->planFeature($slug);
        
        if (!$planFeature || $planFeature->feature->isNonConsumable()) {
            return [
                'plan_limit' => 0,
                'extra_credits' => 0,
                'total_available' => 0,
                'used' => 0,
                'remaining' => 0,
            ];
        }

        $planLimit = $planFeature->isUnlimited() ? 'unlimited' : floatval($planFeature->value);
        $extraCredits = $this->totalExtraCredits($slug);
        $used = $this->totalFeatureUsageInPeriod($slug);

        if ($planLimit === 'unlimited') {
            return [
                'plan_limit' => 'unlimited',
                'extra_credits' => $extraCredits,
                'total_available' => 'unlimited',
                'used' => $used,
                'remaining' => 'unlimited',
            ];
        }

        $totalAvailable = $planLimit + $extraCredits;
        $remaining = $totalAvailable - $used;

        return [
            'plan_limit' => $planLimit,
            'extra_credits' => $extraCredits,
            'total_available' => $totalAvailable,
            'used' => $used,
            'remaining' => max(0, $remaining),
        ];
    }
}
