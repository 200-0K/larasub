<?php

namespace Err0r\Larasub\Resources;

use Err0r\Larasub\Models\PlanFeature;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PlanFeature */
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
        $isSubscriptionPresent = $this->subscription instanceof (config('larasub.models.subscription'));

        return [
            'id' => $this->getKey(),
            'value' => $this->value,
            'display_value' => $this->display_value,
            'reset_period' => $this->reset_period,
            'reset_period_type' => $this->reset_period_type,
            'sort_order' => $this->sort_order,
            'plan_version' => new (config('larasub.resources.plan_version'))($this->whenLoaded('planVersion')),
            'feature' => new (config('larasub.resources.feature'))($this->whenLoaded('feature')),
            $this->mergeWhen($isSubscriptionPresent && $this->feature->isConsumable(), fn () => [
                'total_usages' => $this->subscription->totalFeatureUsageInPeriod($this->feature->slug),
                'remaining_usages' => $this->subscription->remainingFeatureUsage($this->feature->slug),
                'next_reset_at' => $this->subscription->nextAvailableFeatureUsage($this->feature->slug),
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
