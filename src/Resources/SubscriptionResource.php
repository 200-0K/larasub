<?php

namespace Err0r\Larasub\Resources;

use Err0r\Larasub\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Subscription */
class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'cancelled_at' => $this->cancelled_at,
            'subscriber' => $this->whenLoaded('subscriber'),
            'plan_version' => $this->whenLoaded('planVersion', fn () => new (config('larasub.resources.plan_version'))($this->planVersion, $this->resource)),
            'plan' => $this->whenLoaded('planVersion.plan', fn () => new (config('larasub.resources.plan'))($this->planVersion->plan)),
            'features_usage' => config('larasub.resources.subscription_feature_usage')::collection($this->whenLoaded('featuresUsage')),
            'renewed_from' => new (config('larasub.resources.subscription'))($this->whenLoaded('renewedFrom')),
            'renewal' => new (config('larasub.resources.subscription'))($this->whenLoaded('renewal')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
