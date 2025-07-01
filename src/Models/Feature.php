<?php

namespace Err0r\Larasub\Models;

use Err0r\Larasub\Builders\FeatureBuilder;
use Err0r\Larasub\Enums\FeatureType;
use Err0r\Larasub\Traits\HasConfigurableIds;
use Err0r\Larasub\Traits\Sluggable;
use Err0r\Larasub\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * @property string $slug
 * @property string $name
 * @property ?string $description
 * @property FeatureType $type
 * @property int $sort_order
 * @property ?\Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PlanFeature> $plans
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Subscription> $subscriptions
 */
class Feature extends Model
{
    use HasConfigurableIds;
    use HasFactory;
    use HasTranslations;
    use Sluggable;
    use SoftDeletes;
    use Sortable;

    public $translatable = ['name', 'description'];

    protected $fillable = [
        'slug',
        'name',
        'description',
        'type',
        'sort_order',
    ];

    protected $casts = [
        'type' => FeatureType::class,
        'sort_order' => 'integer',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('larasub.tables.features.name'));
    }

    protected function usesUuids(): bool
    {
        return config('larasub.tables.features.uuid');
    }

    /**
     * @return HasMany<PlanFeature, $this>
     */
    public function planFeatures(): HasMany
    {
        /** @var class-string<PlanFeature> */
        $class = config('larasub.models.plan_feature');

        return $this->hasMany($class);
    }

    /**
     * @return BelongsToMany<PlanVersion, $this>
     */
    public function planVersions(): BelongsToMany
    {
        /** @var class-string<PlanVersion> */
        $related = config('larasub.models.plan_version');
        $table = config('larasub.tables.plan_features.name');

        return $this->belongsToMany($related, $table, 'feature_id', 'plan_version_id');
    }

    /**
     * @return HasMany<SubscriptionFeatureUsage, $this>
     */
    public function subscriptionFeatureUsages(): HasMany
    {
        /** @var class-string<SubscriptionFeatureUsage> */
        $class = config('larasub.models.subscription_feature_usages');

        return $this->hasMany($class);
    }

    /**
     * @return BelongsToMany<Subscription, $this>
     */
    public function subscriptions(): BelongsToMany
    {
        /** @var class-string<Subscription> */
        $related = config('larasub.models.subscription');
        $table = config('larasub.tables.subscription_feature_usages.name');

        return $this->belongsToMany($related, $table, 'feature_id', 'subscription_id')
            ->withPivot(['value']);
    }

    /**
     * @return HasMany<SubscriptionFeatureCredit, $this>
     */
    public function subscriptionFeatureCredits(): HasMany
    {
        /** @var class-string<SubscriptionFeatureCredit> */
        $class = config('larasub.models.subscription_feature_credits');

        return $this->hasMany($class);
    }

    public function isConsumable(): bool
    {
        return $this->type == FeatureType::CONSUMABLE;
    }

    public function isNonConsumable(): bool
    {
        return $this->type == FeatureType::NON_CONSUMABLE;
    }

    public static function builder(string $slug): FeatureBuilder
    {
        return FeatureBuilder::create($slug);
    }
}
