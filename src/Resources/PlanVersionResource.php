<?php

namespace Err0r\Larasub\Resources;

use Err0r\Larasub\Models\PlanVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PlanVersion */
class PlanVersionResource extends JsonResource
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
            'id' => $this->getKey(),
            'plan_id' => $this->resource->plan_id,
            'version_number' => $this->version_number,
            'version_label' => $this->version_label,
            'display_version' => $this->getDisplayVersion(),
            'price' => $this->price,
            'currency' => $this->currency,
            'reset_period' => $this->reset_period,
            'reset_period_type' => $this->reset_period_type,
            'is_active' => $this->is_active,
            'published_at' => $this->published_at,
            'plan' => $this->whenLoaded('plan', function () {
                return new (config('larasub.resources.plan'))($this->plan);
            }),
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
