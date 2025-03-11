<?php

namespace Err0r\Larasub\Models;

use Err0r\Larasub\Traits\HasConfigurableIds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|int $subscription_id
 * @property string|int $feature_id
 * @property string $value
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Subscription $subscription
 * @property-read Feature $feature
 */
class SubscriptionFeatureUsage extends Model
{
    use HasConfigurableIds;
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'feature_id',
        'value',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('larasub.tables.subscription_feature_usages.name'));
    }

    protected function usesUuids(): bool
    {
        return config('larasub.tables.subscription_feature_usages.uuid');
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
}
