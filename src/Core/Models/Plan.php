<?php

namespace Err0r\Larasub\Core\Models;

use Carbon\Carbon;
use Err0r\Larasub\Core\Contracts\PlanContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
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
class Plan extends Model implements PlanContract
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
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

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'period_count' => 'integer',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * The default values for attributes.
     */
    protected $attributes = [
        'currency' => 'USD',
        'period' => 'month',
        'period_count' => 1,
        'is_active' => true,
        'sort_order' => 0,
        'metadata' => '{}',
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
     * Scope to order by sort order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    /**
     * Check if plan is free
     */
    public function isFree(): bool
    {
        return $this->price <= 0;
    }

    /**
     * Check if plan is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
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
    public function calculateEndDate(?Carbon $startDate = null): Carbon
    {
        $startDate = $startDate ?: now();

        return match($this->period) {
            'day' => $startDate->copy()->addDays($this->period_count),
            'week' => $startDate->copy()->addWeeks($this->period_count),
            'month' => $startDate->copy()->addMonths($this->period_count),
            'year' => $startDate->copy()->addYears($this->period_count),
            default => $startDate->copy()->addMonths($this->period_count),
        };
    }

    /**
     * Get the plan's display price with currency
     */
    public function getFormattedPrice(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }

        return sprintf('%s %s', $this->currency, number_format($this->price, 2));
    }

    /**
     * Get the plan's period in human readable format
     */
    public function getHumanReadablePeriod(): string
    {
        $period = $this->period_count === 1 
            ? $this->period 
            : "{$this->period_count} {$this->period}s";

        return ucfirst($period);
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}