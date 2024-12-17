<?php

namespace Err0r\Larasub\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanFeatureResource extends JsonResource
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
            'value' => $this->value,
            'display_value' => $this->display_value,
            'reset_period' => $this->reset_period,
            'reset_period_type' => $this->reset_period_type,
            'sort_order' => $this->sort_order,
            'plan' => new (config('larasub.resources.plan'))($this->whenLoaded('plan')),
            'feature' => new (config('larasub.resources.feature'))($this->whenLoaded('feature')),
            $this->mergeWhen($this->subscription && $this->feature->isConsumable(), fn () => [
                'total_usages' => $this->subscription->totalFeatureUsageInPeriod($this->feature->slug),
                'remaining_usages' => $this->subscription->remainingFeatureUsage($this->feature->slug),
                'next_reset_at' => $this->subscription->nextAvailableFeatureUsage($this->feature->slug),
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
