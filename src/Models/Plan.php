<?php

namespace Err0r\Larasub\Models;

use Err0r\Larasub\Builders\PlanBuilder;
use Err0r\Larasub\Enums\Period;
use Err0r\Larasub\Traits\HasConfigurableIds;
use Err0r\Larasub\Traits\Sluggable;
use Err0r\Larasub\Traits\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * @property string $slug
 * @property string $name
 * @property string $description
 * @property bool $is_active
 * @property float $price
 * @property string $currency
 * @property int $reset_period
 * @property Period $reset_period_type
 * @property int $sort_order
 * @property \Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
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

    public $translatable = ['name', 'description', 'currency'];

    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_active',
        'price',
        'currency',
        'reset_period',
        'reset_period_type',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'float',
        'reset_period' => 'integer',
        'reset_period_type' => Period::class,
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
     * @return HasMany<PlanFeature, $this>
     */
    public function features(): HasMany
    {
        /** @var class-string<PlanFeature> */
        $class = config('larasub.models.plan_feature');

        return $this->hasMany($class);
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        /** @var class-string<Subscription> */
        $class = config('larasub.models.subscription');

        return $this->hasMany($class);
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
        $this->load('features.feature');

        return $this->features->first(
            fn (PlanFeature $planFeature) => $planFeature->feature->slug === $slug
        );
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isFree(): bool
    {
        return $this->price == 0;
    }

    public static function builder(string $slug): PlanBuilder
    {
        return PlanBuilder::create($slug);
    }
}
