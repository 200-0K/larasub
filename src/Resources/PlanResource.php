<?php

namespace Err0r\Larasub\Resources;

use Err0r\Larasub\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Plan */
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
            'id' => $this->getKey(),
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'current_version' => $this->whenLoaded('currentVersion', function () {
                return new (config('larasub.resources.plan_version'))($this->currentVersion, $this->subscription);
            }),
            'versions' => $this->whenLoaded('versions', function () {
                return $this->versions->map(function ($version) {
                    return new (config('larasub.resources.plan_version'))($version, $this->subscription);
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
