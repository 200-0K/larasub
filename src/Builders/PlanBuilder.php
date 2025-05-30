<?php

namespace Err0r\Larasub\Builders;

use Err0r\Larasub\Enums\Period;
use Err0r\Larasub\Models\Feature;
use Err0r\Larasub\Models\Plan;
use Err0r\Larasub\Models\PlanVersion;

class PlanBuilder
{
    private array $planAttributes = [];

    private array $versionAttributes = [];

    private array $features = [];

    public function __construct(string $slug)
    {
        $this->planAttributes['slug'] = $slug;
        $this->planAttributes['is_active'] = true;
        $this->planAttributes['sort_order'] = 0;

        $this->versionAttributes['version_number'] = null; // Will be auto-calculated
        $this->versionAttributes['version_label'] = null;
        $this->versionAttributes['price'] = 0.0;
        $this->versionAttributes['is_active'] = true;
    }

    public static function create(string $slug): self
    {
        return new self($slug);
    }

    public function name($name): self
    {
        $this->planAttributes['name'] = $name;

        return $this;
    }

    public function description($description): self
    {
        $this->planAttributes['description'] = $description;

        return $this;
    }

    public function versionNumber(int $versionNumber): self
    {
        $this->versionAttributes['version_number'] = $versionNumber;

        return $this;
    }

    public function versionLabel(string $versionLabel): self
    {
        $this->versionAttributes['version_label'] = $versionLabel;

        return $this;
    }

    /**
     * @deprecated Use versionLabel() instead
     */
    public function version(string $version): self
    {
        $this->versionAttributes['version_label'] = $version;

        return $this;
    }

    public function price(float $price, $currency): self
    {
        $this->versionAttributes['price'] = $price;
        $this->versionAttributes['currency'] = $currency;

        return $this;
    }

    public function resetPeriod(int $period, Period $periodType): self
    {
        $this->versionAttributes['reset_period'] = $period;
        $this->versionAttributes['reset_period_type'] = $periodType;

        return $this;
    }

    public function inactive(): self
    {
        $this->planAttributes['is_active'] = false;

        return $this;
    }

    public function versionInactive(): self
    {
        $this->versionAttributes['is_active'] = false;

        return $this;
    }

    public function published(): self
    {
        $this->versionAttributes['published_at'] = now();

        return $this;
    }

    public function sortOrder(int $order): self
    {
        $this->planAttributes['sort_order'] = $order;

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
        // Create or update the plan
        $plan = Plan::updateOrCreate(
            ['slug' => $this->planAttributes['slug']],
            $this->planAttributes
        );

        // Auto-calculate version number if not set
        if ($this->versionAttributes['version_number'] === null) {
            $this->versionAttributes['version_number'] = PlanVersion::getNextVersionNumber($plan);
        }

        // Create or update the plan version
        $this->versionAttributes['plan_id'] = $plan->getKey();
        $planVersion = PlanVersion::updateOrCreate(
            ['plan_id' => $plan->getKey(), 'version_number' => $this->versionAttributes['version_number']],
            $this->versionAttributes
        );

        // Attach features to the plan version
        foreach ($this->features as $featureData) {
            // The plan_version_id is automatically set by the relationship, but let's be explicit
            $featureData['plan_version_id'] = $planVersion->getKey();

            $planVersion->features()->updateOrCreate(
                ['feature_id' => $featureData['feature_id']],
                $featureData
            );
        }

        // Delete features that are not in the new features array (only if features were provided)
        if (! empty($this->features)) {
            $featureIds = array_column($this->features, 'feature_id');
            $planVersion->features()->whereNotIn('feature_id', $featureIds)->delete();
        }

        return $plan;
    }
}
