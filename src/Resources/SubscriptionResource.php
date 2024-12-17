<?php

namespace Err0r\Larasub\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'id' => $this->id,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'cancelled_at' => $this->cancelled_at,
            'subscriber' => $this->whenLoaded('subscriber'),
            'plan' => $this->whenLoaded('plan', fn () => new (config('larasub.resources.plan'))($this->plan, $this->resource)),
            'features_usage' => config('larasub.resources.subscription_feature_usage')::collection($this->whenLoaded('featuresUsage')),
            'renewed_from' => new (config('larasub.resources.subscription'))($this->whenLoaded('renewedFrom')),
            'renewal' => new (config('larasub.resources.subscription'))($this->whenLoaded('renewal')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
