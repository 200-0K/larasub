<?php

namespace Err0r\Larasub\Resources;

use Err0r\Larasub\Models\SubscriptionFeatureUsage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubscriptionFeatureUsage */
class SubscriptionFeatureUsageResource extends JsonResource
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
            'value' => $this->value,
            'subscription' => new (config('larasub.resources.subscription'))($this->whenLoaded('subscription')),
            'feature' => new (config('larasub.resources.feature'))($this->whenLoaded('feature')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
