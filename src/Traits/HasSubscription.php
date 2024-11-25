<?php

namespace Err0r\Larasub\Traits;

use Err0r\Larasub\Facades\SubscriptionService;
use Err0r\Larasub\Models\Feature;
use Err0r\Larasub\Models\Subscription;
use Err0r\Larasub\Models\SubscriptionFeatureUsage;
use Illuminate\Database\Eloquent\Collection;

trait HasSubscription
{
    use Subscribable;

    /**
     * Get the latest active subscription.
     *
     * @return Subscription
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
        $subscription = SubscriptionService::validateActiveSubscription($this);

        return $subscription->featuresUsage()->get();
    }

    /**
     * Get the usage of a specific feature for the active subscription.
     *
     * @return Collection<SubscriptionFeatureUsage>
     */
    public function featureUsage(string $slug)
    {
        $subscription = SubscriptionService::validateActiveSubscription($this);

        return $subscription->featureUsage($slug)->get();
    }

    /**
     * Get a specific feature for the active subscriptions.
     *
     * @return Feature|null
     */
    public function planFeature(string $slug)
    {
        $subscription = SubscriptionService::validateActiveSubscription($this);

        return $subscription->planFeature($slug);
    }

    /**
     * Check if the feature exists in the active subscription.
     */
    public function hasFeature(string $slug): bool
    {
        $subscription = SubscriptionService::validateActiveSubscription($this);

        return $subscription->hasFeature($slug);
    }

    /**
     * Check if features exist in the active subscription.
     */
    public function hasFeatures(iterable $slugs): bool
    {
        $subscription = SubscriptionService::validateActiveSubscription($this);

        return collect($slugs)->every(fn ($slug) => $subscription->hasFeature($slug));
    }

    /**
     * Get the remaining usage of a specific feature for the active subscription.
     */
    public function remainingFeatureUsage(string $slug): ?float
    {
        $subscription = SubscriptionService::validateActiveSubscription($this);

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
        $subscription = SubscriptionService::validateActiveSubscription($this);

        return $subscription->nextAvailableFeatureUsage($slug);
    }

    /**
     * Check if the feature is available for use in the active subscription.
     */
    public function canUseFeature(string $slug, float $value): bool
    {
        $subscription = SubscriptionService::validateActiveSubscription($this);

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
        $subscription = SubscriptionService::validateActiveSubscription($this);

        return $subscription->useFeature($slug, $value);
    }
}
