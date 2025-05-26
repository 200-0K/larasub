<?php

namespace Err0r\Larasub\Traits;

use Carbon\Carbon;
use Err0r\Larasub\Facades\SubscriptionHelperService;
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
     * @return SubscriptionFeatureUsage[]
     *
     * @throws \InvalidArgumentException
     */
    public function useFeature(string $slug, float $value)
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        return $subscription->useFeature($slug, $value);
    }

    /**
     * Add an add-on for a specific feature.
     *
     * @param  string  $slug  The feature slug
     * @param  string|float|null  $value  Value for the add-on
     * @param  array{expires_at?: Carbon|string|null, reference?: string|null}  $options  Additional options
     *                                                                                    - expires_at: Carbon|string|null (when the add-on expires)
     *                                                                                    - reference: string|null (external reference)
     * @return \Err0r\Larasub\Models\SubscriptionFeatureAddon
     */
    public function addFeatureAddon(string $slug, $value = null, array $options = [])
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        return $subscription->addFeatureAddon($slug, $value, $options);
    }

    /**
     * Get remaining add-on usage for a specific feature.
     *
     * @param  string  $slug  The feature slug
     */
    public function remainingFeatureAddonUsage(string $slug): float
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        return $subscription->remainingFeatureAddonUsage($slug);
    }

    /**
     * Check if any active add-on provides access to a feature.
     *
     * @param  string  $slug  The feature slug
     */
    public function hasFeatureAddonAccess(string $slug): bool
    {
        $subscription = SubscriptionHelperService::validateActiveSubscription($this);

        return $subscription->hasFeatureAddonAccess($slug);
    }
}
