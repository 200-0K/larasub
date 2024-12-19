<?php

namespace Err0r\Larasub\Models;

use Err0r\Larasub\Builders\PlanBuilder;
use Err0r\Larasub\Enums\Period;
use Err0r\Larasub\Traits\Sluggable;
use Err0r\Larasub\Traits\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Plan extends Model
{
    use HasFactory;
    use HasTranslations;
    use HasUuids;
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

    /**
     * @return HasMany<PlanFeature>
     */
    public function features(): HasMany
    {
        return $this->hasMany(config('larasub.models.plan_feature'));
    }

    /**
     * @return HasMany<Subscription>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(config('larasub.models.subscription'));
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

        return $this->features->first(fn ($feature) => $feature->feature->slug === $slug);
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
