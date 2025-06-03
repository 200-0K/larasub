<?php

namespace Err0r\Larasub\Models;

use Err0r\Larasub\Enums\FeatureValue;
use Err0r\Larasub\Enums\Period;
use Err0r\Larasub\Traits\HasConfigurableIds;
use Err0r\Larasub\Traits\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * @property ?string $value
 * @property ?string $display_value
 * @property ?int $reset_period
 * @property ?Period $reset_period_type
 * @property bool $is_hidden
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read PlanVersion $planVersion
 * @property-read Feature $feature
 */
class PlanFeature extends Model
{
    use HasConfigurableIds;
    use HasFactory;
    use HasTranslations;
    use Sortable;

    public $translatable = ['display_value'];

    protected $fillable = [
        'plan_version_id',
        'feature_id',
        'value',
        'display_value',
        'reset_period',
        'reset_period_type',
        'is_hidden',
        'sort_order',
    ];

    protected $casts = [
        'reset_period' => 'integer',
        'reset_period_type' => Period::class,
        'is_hidden' => 'boolean',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('larasub.tables.plan_features.name'));
    }

    protected function usesUuids(): bool
    {
        return config('larasub.tables.plan_features.uuid');
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
     * Scope a query to only include visible features.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope a query to only include hidden features.
     */
    public function scopeHidden(Builder $query): Builder
    {
        return $query->where('is_hidden', true);
    }

    public function isUnlimited(): bool
    {
        return $this->value === FeatureValue::UNLIMITED->value;
    }

    public function isVisible(): bool
    {
        return !$this->is_hidden;
    }

    public function isHidden(): bool
    {
        return $this->is_hidden;
    }
}
