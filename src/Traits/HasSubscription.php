<?php

namespace Err0r\Larasub\Traits;

use Err0r\Larasub\Facades\SubscriptionHelperService;
use Err0r\Larasub\Models\Feature;
use Err0r\Larasub\Models\PlanFeature;
use Err0r\Larasub\Models\Subscription;
use Err0r\Larasub\Models\SubscriptionFeatureUsage;
use Illuminate\Database\Eloquent\Collection;

trait HasSubscription
{
    use Subscribable;

    /**
     * Get the latest active subscription.
     *
     * @return Subscription|null
     */
    public function activeSubscription()
    {
        return $this->subscriptions()->active()->latest('start_at')->first();
    }

    /**
     * Check if the subscriber has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    /**
     * Get features usage for the active subscription.
     *
     * @return Collection<SubscriptionFeatureUsage>
     */
    public function featuresUsage()
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        return $subscription->featuresUsage()->get();
    }

    /**
     * Get the usage of a specific feature for the active subscription.
     *
     * @return Collection<SubscriptionFeatureUsage>
     */
    public function featureUsage(string $slug)
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        return $subscription->featureUsage($slug)->get();
    }

    /**
     * Get a specific feature for the active subscriptions.
     *
     * @return PlanFeature|null
     */
    public function planFeature(string $slug)
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        return $subscription->planFeature($slug);
    }

    /**
     * Check if the feature exists in the active subscription.
     */
    public function hasFeature(string $slug): bool
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        return $subscription->hasFeature($slug);
    }

    /**
     * Check if features exist in the active subscription.
     */
    public function hasFeatures(iterable $slugs): bool
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        return collect($slugs)->every(fn ($slug) => $subscription->hasFeature($slug));
    }

    /**
     * Get the remaining usage of a specific feature for the active subscription.
     */
    public function remainingFeatureUsage(string $slug): ?float
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        return $subscription->remainingFeatureUsage($slug);
    }

    /**
     * Get the next time a feature will be available for use
     *
     * @param  string  $slug  The feature slug to check
     * @return \Carbon\Carbon|bool|null
     *
     * @throws \InvalidArgumentException
     *
     * @see \Err0r\Larasub\Models\Subscription::nextAvailableFeatureUsage
     */
    public function nextAvailableFeatureUsage(string $slug)
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        return $subscription->nextAvailableFeatureUsage($slug);
    }

    /**
     * Check if the feature is available for use in the active subscription.
     */
    public function canUseFeature(string $slug, float $value): bool
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        return $subscription->canUseFeature($slug, $value);
    }

    /**
     * Use a specific feature for the first applicable active subscription.
     *
     * @return SubscriptionFeatureUsage
     *
     * @throws \InvalidArgumentException
     */
    public function useFeature(string $slug, float $value)
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        return $subscription->useFeature($slug, $value);
    }

    /**
     * Grant extra credits for a specific feature to the active subscription
     *
     * @param string $slug Feature slug
     * @param float $credits Number of credits to grant
     * @param string|null $reason Reason for granting credits
     * @param array $options Additional options
     * @return SubscriptionFeatureCredit
     */
    public function grantExtraCredits(string $slug, float $credits, ?string $reason = null, array $options = []): SubscriptionFeatureCredit
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        $options['reason'] = $reason;
        
        return $subscription->grantExtraCredits($slug, $credits, $options);
    }

    /**
     * Get total extra credits for a feature in the active subscription
     *
     * @param string $slug Feature slug
     * @return float
     */
    public function extraCredits(string $slug): float
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        return $subscription->totalExtraCredits($slug);
    }

    /**
     * Get total available credits (plan + extra) for a feature
     *
     * @param string $slug Feature slug
     * @return float|string 'unlimited' if unlimited
     */
    public function totalAvailableCredits(string $slug)
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);
        
        $stats = $subscription->getCreditUsageStats($slug);
        
        return $stats['total_available'];
    }

    /**
     * Get credit usage statistics for a feature
     *
     * @param string $slug Feature slug
     * @return array
     */
    public function getCreditUsageStats(string $slug): array
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        return $subscription->getCreditUsageStats($slug);
    }

    /**
     * Grant multiple extra credits at once
     *
     * @param array $credits Array of slug => credit_amount
     * @param string|null $reason Reason for granting credits
     * @param array $options Additional options
     * @return array Array of SubscriptionFeatureCredit instances
     */
    public function grantMultipleExtraCredits(array $credits, ?string $reason = null, array $options = []): array
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);
        
        $results = [];
        
        foreach ($credits as $slug => $amount) {
            $creditOptions = $options;
            $creditOptions['reason'] = $reason;
            
            $results[$slug] = $subscription->grantExtraCredits($slug, $amount, $creditOptions);
        }
        
        return $results;
    }
}
