<?php

namespace Err0r\Larasub\Builders;

use Err0r\Larasub\Enums\Period;
use Err0r\Larasub\Models\Feature;
use Err0r\Larasub\Models\Plan;

class PlanBuilder
{
    private array $attributes = [];

    private array $features = [];

    public function __construct(string $slug)
    {
        $this->attributes['slug'] = $slug;
        $this->attributes['is_active'] = true;
        $this->attributes['price'] = 0.0;
    }

    public static function create(string $slug): self
    {
        return new self($slug);
    }

    public function name($name): self
    {
        $this->attributes['name'] = $name;

        return $this;
    }

    public function description($description): self
    {
        $this->attributes['description'] = $description;

        return $this;
    }

    public function price(float $price, $currency): self
    {
        $this->attributes['price'] = $price;
        $this->attributes['currency'] = $currency;

        return $this;
    }

    public function resetPeriod(int $period, Period $periodType): self
    {
        $this->attributes['reset_period'] = $period;
        $this->attributes['reset_period_type'] = $periodType;

        return $this;
    }

    public function inactive(): self
    {
        $this->attributes['is_active'] = false;

        return $this;
    }

    public function sortOrder(int $order): self
    {
        $this->attributes['sort_order'] = $order;

        return $this;
    }

    /**
     * @param  Feature|string  $feature  The feature model or slug
     * @param  callable(PlanFeatureBuilder): PlanFeatureBuilder  $callback  The callback to build the feature
     */
    public function addFeature($feature, callable $callback): self
    {
        $featureSlug = $feature instanceof Feature ? $feature->slug : $feature;

        $featureBuilder = new PlanFeatureBuilder($featureSlug);
        $callback($featureBuilder);
        $this->features[] = $featureBuilder->build();

        return $this;
    }

    public function build(): Plan
    {
        $plan = Plan::updateOrCreate(
            ['slug' => $this->attributes['slug']],
            $this->attributes
        );

        // Attach features
        foreach ($this->features as $feature) {
            $plan->features()->updateOrCreate(
                ['feature_id' => $feature['feature_id']],
                $feature
            );
        }

        return $plan;
    }
}
