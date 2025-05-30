<?php

namespace Err0r\Larasub\Models;

use Err0r\Larasub\Enums\Period;
use Err0r\Larasub\Traits\HasConfigurableIds;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * @property string|int $plan_id
 * @property int $version_number
 * @property string $version_label
 * @property float $price
 * @property string $currency
 * @property int $reset_period
 * @property Period $reset_period_type
 * @property bool $is_active
 * @property \Carbon\Carbon $published_at
 * @property \Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Plan $plan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PlanFeature> $features
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Subscription> $subscriptions
 */
class PlanVersion extends Model
{
    use HasConfigurableIds;
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    public $translatable = ['currency'];

    protected $fillable = [
        'plan_id',
        'version_number',
        'version_label',
        'price',
        'currency',
        'reset_period',
        'reset_period_type',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'price' => 'float',
        'reset_period' => 'integer',
        'reset_period_type' => Period::class,
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('larasub.tables.plan_versions.name'));
    }

    protected function usesUuids(): bool
    {
        return config('larasub.tables.plan_versions.uuid');
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        /** @var class-string<Plan> */
        $class = config('larasub.models.plan');

        return $this->belongsTo($class);
    }

    /**
     * @return HasMany<PlanFeature, $this>
     */
    public function features(): HasMany
    {
        /** @var class-string<PlanFeature> */
        $class = config('larasub.models.plan_feature');

        return $this->hasMany($class, 'plan_version_id');
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        /** @var class-string<Subscription> */
        $class = config('larasub.models.subscription');

        return $this->hasMany($class, 'plan_version_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('version_number', 'desc');
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

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function publish(): bool
    {
        return $this->update(['published_at' => now()]);
    }

    public function unpublish(): bool
    {
        return $this->update(['published_at' => null]);
    }

    /**
     * Get the display version (label if available, otherwise version number)
     */
    public function getDisplayVersion(): string
    {
        return $this->version_label ?? "v{$this->version_number}";
    }

    /**
     * Check if this version is newer than another version
     */
    public function isNewerThan(PlanVersion $other): bool
    {
        return $this->version_number > $other->version_number;
    }

    /**
     * Check if this version is older than another version
     */
    public function isOlderThan(PlanVersion $other): bool
    {
        return $this->version_number < $other->version_number;
    }

    /**
     * Get the next version number for this plan
     *
     * @param  int|string|Plan  $planId
     */
    public static function getNextVersionNumber($planId): int
    {
        $planId = $planId instanceof Plan ? $planId->getKey() : $planId;

        return static::where('plan_id', $planId)->max('version_number') + 1;
    }
}
