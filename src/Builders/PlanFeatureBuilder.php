<?php

namespace Err0r\Larasub\Builders;

use Err0r\Larasub\Enums\FeatureType;
use Err0r\Larasub\Enums\FeatureValue;
use Err0r\Larasub\Enums\Period;
use Err0r\Larasub\Models\Feature;

class PlanFeatureBuilder
{
    private array $attributes = [];

    public function __construct(string $featureSlug)
    {
        $this->attributes['slug'] = $featureSlug;
    }

    /**
     * @param  FeatureValue|string|null  $value
     */
    public function value($value): self
    {
        $value = $value instanceof FeatureValue ? $value->value : $value;
        $this->attributes['value'] = $value;

        return $this;
    }

    /**
     * @param  string|array|null  $displayValue
     */
    public function displayValue($displayValue): self
    {
        $this->attributes['display_value'] = $displayValue;

        return $this;
    }

    public function resetPeriod(?int $resetPeriod, ?Period $resetPeriodType): self
    {
        $this->attributes['reset_period'] = $resetPeriod;
        $this->attributes['reset_period_type'] = $resetPeriodType;

        return $this;
    }

    public function sortOrder(?int $sortOrder): self
    {
        $this->attributes['sort_order'] = $sortOrder;

        return $this;
    }

    public function hidden(bool $isHidden = true): self
    {
        $this->attributes['is_hidden'] = $isHidden;

        return $this;
    }

    public function visible(): self
    {
        $this->attributes['is_hidden'] = false;

        return $this;
    }

    public function build(): array
    {
        $featureModel = Feature::where('slug', $this->attributes['slug'])->firstOrFail();

        if ($featureModel->type === FeatureType::CONSUMABLE && ($this->attributes['value'] ?? null) === null) {
            throw new \InvalidArgumentException("The feature '{$this->attributes['slug']}' is consumable and requires a value");
        }

        return [
            'feature_id' => $featureModel->id,
            'value' => $this->attributes['value'] ?? null,
            'display_value' => $this->attributes['display_value'] ?? null,
            'reset_period' => $this->attributes['reset_period'] ?? null,
            'reset_period_type' => $this->attributes['reset_period_type'] ?? null,
            'is_hidden' => $this->attributes['is_hidden'] ?? false,
            'sort_order' => $this->attributes['sort_order'] ?? null,
        ];
    }
}
