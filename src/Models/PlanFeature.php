<?php

namespace Err0r\Larasub\Models;

use Err0r\Larasub\Enums\FeatureValue;
use Err0r\Larasub\Enums\Period;
use Err0r\Larasub\Traits\Sortable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class PlanFeature extends Model
{
    use HasFactory;
    use HasTranslations;
    use HasUuids;
    use Sortable;

    public $translatable = ['display_value'];

    protected $fillable = [
        'plan_id',
        'feature_id',
        'value',
        'display_value',
        'reset_period',
        'reset_period_type',
        'sort_order',
    ];

    protected $casts = [
        'reset_period' => 'integer',
        'reset_period_type' => Period::class,
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('larasub.tables.plan_features.name'));
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(config('larasub.models.plan'));
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(config('larasub.models.feature'));
    }

    public function isUnlimited(): bool
    {
        return $this->value === FeatureValue::UNLIMITED->value;
    }
}
