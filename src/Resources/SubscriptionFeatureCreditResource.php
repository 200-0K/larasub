<?php

namespace Err0r\Larasub\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Err0r\Larasub\Models\SubscriptionFeatureCredit
 */
class SubscriptionFeatureCreditResource extends JsonResource
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
            'subscription_id' => $this->subscription_id,
            'feature_id' => $this->feature_id,
            'feature' => $this->whenLoaded('feature', function () {
                /** @var class-string<FeatureResource> */
                $resourceClass = config('larasub.resources.feature');
                return new $resourceClass($this->feature);
            }),
            'subscription' => $this->whenLoaded('subscription', function () {
                /** @var class-string<SubscriptionResource> */
                $resourceClass = config('larasub.resources.subscription');
                return new $resourceClass($this->subscription);
            }),
            'credits' => (float) $this->credits,
            'reason' => $this->reason,
            'granted_by_type' => $this->granted_by_type,
            'granted_by_id' => $this->granted_by_id,
            'granted_by' => $this->whenLoaded('grantedBy'),
            'expires_at' => $this->expires_at?->toISOString(),
            'is_expired' => $this->isExpired(),
            'is_active' => $this->isActive(),
            'days_until_expiration' => $this->daysUntilExpiration(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}