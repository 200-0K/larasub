<?php

namespace Err0r\Larasub\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Simple Plan Model
 * 
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property ?string $description
 * @property float $price
 * @property string $currency
 * @property string $period (day|week|month|year)
 * @property int $period_count
 * @property array $metadata
 * @property bool $is_active
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property ?\Carbon\Carbon $deleted_at
 */
class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'period',
        'period_count',
        'metadata',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'float',
        'period_count' => 'integer',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'currency' => 'USD',
        'period' => 'month',
        'period_count' => 1,
        'metadata' => '{}',
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        $this->setTable(config('larasub.tables.plans', 'plans'));
        
        if (config('larasub.use_uuid', false)) {
            $this->keyType = 'string';
            $this->incrementing = false;
        }
    }

    /**
     * Get all subscriptions for this plan
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Scope to only active plans
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to find by slug
     */
    public function scopeSlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * Check if plan is free
     */
    public function isFree(): bool
    {
        return $this->price <= 0;
    }

    /**
     * Get the period in days for calculations
     */
    public function getPeriodInDays(): int
    {
        $days = match($this->period) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            'year' => 365,
            default => 30,
        };

        return $days * $this->period_count;
    }

    /**
     * Calculate end date from a start date
     */
    public function calculateEndDate($startDate = null): \Carbon\Carbon
    {
        $start = $startDate ? \Carbon\Carbon::parse($startDate) : now();

        return match($this->period) {
            'day' => $start->addDays($this->period_count),
            'week' => $start->addWeeks($this->period_count),
            'month' => $start->addMonths($this->period_count),
            'year' => $start->addYears($this->period_count),
            default => $start->addMonths($this->period_count),
        };
    }
}