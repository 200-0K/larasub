<?php

namespace Err0r\Larasub\Builders;

use Err0r\Larasub\Enums\FeatureType;
use Err0r\Larasub\Models\Feature;

class FeatureBuilder
{
    private array $attributes = [];

    public function __construct(string $slug)
    {
        $this->attributes['slug'] = $slug;
        $this->attributes['type'] = FeatureType::NON_CONSUMABLE;
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

    public function consumable(): self
    {
        $this->attributes['type'] = FeatureType::CONSUMABLE;

        return $this;
    }

    public function nonConsumable(): self
    {
        $this->attributes['type'] = FeatureType::NON_CONSUMABLE;

        return $this;
    }

    public function sortOrder(int $order): self
    {
        $this->attributes['sort_order'] = $order;

        return $this;
    }

    public function build(): Feature
    {
        $feature = Feature::updateOrCreate(
            ['slug' => $this->attributes['slug']],
            $this->attributes
        );

        return $feature;
    }
}
