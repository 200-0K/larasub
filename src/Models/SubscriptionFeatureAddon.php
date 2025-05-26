<?php

namespace Err0r\Larasub\Models;

use Err0r\Larasub\Enums\FeatureValue;
use Err0r\Larasub\Traits\HasConfigurableIds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|int $subscription_id
 * @property string|int $feature_id
 * @property string|null $value Similar to plan_feature.value, supports numeric and text values
 * @property string|null $reference External reference (e.g., payment ID)
 * @property \Carbon\Carbon|null $expires_at When this add-on expires
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Subscription $subscription
 * @property-read Feature $feature
 */
class SubscriptionFeatureAddon extends Model
{
    use HasConfigurableIds;
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'feature_id',
        'value',
        'reference',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('larasub.tables.subscription_feature_addons.name'));
    }

    protected function usesUuids(): bool
    {
        return config('larasub.tables.subscription_feature_addons.uuid');
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
     * @return HasMany<SubscriptionFeatureUsage, $this>
     */
    public function usages(): HasMany
    {
        /** @var class-string<SubscriptionFeatureUsage> */
        $class = config('larasub.models.subscription_feature_usages');

        return $this->hasMany($class, 'addon_id');
    }

    /**
     * Check if the add-on is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if the add-on is active.
     */
    public function isActive(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Check if this is a consumable add-on.
     */
    public function isConsumable(): bool
    {
        return $this->feature->isConsumable();
    }

    /**
     * Check if the value represents unlimited usage.
     */
    public function isUnlimited(): bool
    {
        return $this->value === FeatureValue::UNLIMITED->value;
    }

    /**
     * Get the remaining amount for this add-on (for consumable features).
     */
    public function remainingUsage(): float
    {
        if (! $this->isConsumable() || $this->isUnlimited()) {
            return floatval(INF);
        }

        $usedAmount = $this->usages()->sum('value');
        $totalAmount = floatval($this->value);

        return $totalAmount - $usedAmount;
    }
}
