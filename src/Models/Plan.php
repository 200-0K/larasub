<?php

namespace Err0r\Larasub\Models;

use Err0r\Larasub\Builders\PlanBuilder;
use Err0r\Larasub\Traits\HasConfigurableIds;
use Err0r\Larasub\Traits\Sluggable;
use Err0r\Larasub\Traits\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * @property string $slug
 * @property string $name
 * @property string $description
 * @property bool $is_active
 * @property \Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PlanVersion> $versions
 * @property-read PlanVersion $currentVersion
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PlanFeature> $features
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Subscription> $subscriptions
 */
class Plan extends Model
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
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('larasub.tables.plans.name'));
    }

    protected function usesUuids(): bool
    {
        return config('larasub.tables.plans.uuid');
    }

    /**
     * @return HasMany<PlanVersion, $this>
     */
    public function versions(): HasMany
    {
        /** @var class-string<PlanVersion> */
        $class = config('larasub.models.plan_version');

        return $this->hasMany($class);
    }

    /**
     * Get the current active version of the plan
     *
     * @return PlanVersion|null
     */
    public function currentVersion()
    {
        /** @var class-string<PlanVersion> */
        $planVersionClass = config('larasub.models.plan_version');

        return $planVersionClass::where('plan_id', $this->getKey())
            ->active()
            ->published()
            ->latest()
            ->first();
    }

    /**
     * @return HasManyThrough<PlanFeature, PlanVersion, $this>
     */
    public function features(): HasManyThrough
    {
        // TODO: test
        // Will not this get all features for all versions? is this right?

        /** @var class-string<PlanFeature> */
        $planFeatureClass = config('larasub.models.plan_feature');
        /** @var class-string<PlanVersion> */
        $planVersionClass = config('larasub.models.plan_version');

        return $this->hasManyThrough($planFeatureClass, $planVersionClass, 'plan_id', 'plan_version_id');
    }

    /**
     * @return HasManyThrough<Subscription, PlanVersion, $this>
     */
    public function subscriptions(): HasManyThrough
    {
        // TODO: test
        // Will not this get all subscriptions for all versions? is this what we want?

        /** @var class-string<Subscription> */
        $subscriptionClass = config('larasub.models.subscription');
        /** @var class-string<PlanVersion> */
        $planVersionClass = config('larasub.models.plan_version');

        return $this->hasManyThrough($subscriptionClass, $planVersionClass, 'plan_id', 'plan_version_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return PlanFeature|null
     */
    public function feature(string $slug)
    {
        $currentVersion = $this->currentVersion();
        if (! $currentVersion) {
            return null;
        }

        $currentVersion->load('features.feature');

        return $currentVersion->features->first(
            fn (PlanFeature $planFeature) => $planFeature->feature->slug === $slug
        );
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isFree(): bool
    {
        $currentVersion = $this->currentVersion();

        return $currentVersion ? $currentVersion->isFree() : true;
    }

    /**
     * Get the price of the current version
     */
    public function getPrice(): float
    {
        $currentVersion = $this->currentVersion();

        return $currentVersion ? $currentVersion->price : 0.0;
    }

    /**
     * Get the currency of the current version
     */
    public function getCurrency()
    {
        $currentVersion = $this->currentVersion();

        return $currentVersion ? $currentVersion->currency : null;
    }

    /**
     * Check if the current version is published
     */
    public function isPublished(): bool
    {
        $currentVersion = $this->currentVersion();

        return $currentVersion ? $currentVersion->isPublished() : false;
    }

    public static function builder(string $slug): PlanBuilder
    {
        return PlanBuilder::create($slug);
    }
}
