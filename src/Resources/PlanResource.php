<?php

namespace Err0r\Larasub\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function __construct($resource, private $subscription = null)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'price' => $this->price,
            'currency' => $this->currency,
            'reset_period' => $this->reset_period,
            'reset_period_type' => $this->reset_period_type,
            'sort_order' => $this->sort_order,
            'features' => $this->whenLoaded('features', function () {
                return $this->features->map(function ($feature) {
                    return new (config('larasub.resources.plan_feature'))($feature, $this->subscription);
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
