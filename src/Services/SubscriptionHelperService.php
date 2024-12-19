<?php

namespace Err0r\Larasub\Services;

use Carbon\Carbon;
use Err0r\Larasub\Facades\PeriodService;
use Err0r\Larasub\Facades\PlanService;
use Err0r\Larasub\Models\PlanFeature;
use Illuminate\Database\Eloquent\Collection;

final class SubscriptionHelperService
{
    /**
     * @param  \Err0r\Larasub\Models\Subscription  $subscription
     * @return PlanFeature
     *
     * @throws \InvalidArgumentException
     */
    private function validateSubscriptionFeature($subscription, string $slug)
    {
        $planFeature = $subscription->planFeature($slug);

        if ($planFeature === null) {
            throw new \InvalidArgumentException("The feature '$slug' is not part of the plan");
        }

        return $planFeature;
    }

    /**
     * @return \Err0r\Larasub\Models\Subscription
     *
     * @throws \InvalidArgumentException
     */
    public function validateActiveSubscription($subscriber)
    {
        $subscription = $subscriber->activeSubscription();

        if (! $subscription) {
            throw new \InvalidArgumentException('No active subscription found.');
        }

        return $subscription;
    }

    /**
     * @param  \Err0r\Larasub\Models\Subscription  $subscription
     * @return \Illuminate\Database\Eloquent\Collection<\Err0r\Larasub\Models\SubscriptionFeatureUsage>
     *
     * @throws \InvalidArgumentException
     */
    public function featureUsageInPeriod($subscription, string $slug): Collection
    {
        $planFeature = $this->validateSubscriptionFeature($subscription, $slug);

        $usages = $subscription->featureUsage($slug);
        if ($planFeature->reset_period !== null && $planFeature->reset_period_type !== null) {
            $resetPeriod = $planFeature->reset_period;
            $resetPeriodType = $planFeature->reset_period_type;
            $resetMinutes = PeriodService::getMinutes($resetPeriod, $resetPeriodType);
            $usages = $usages->where('created_at', '>=', now()->subMinutes($resetMinutes));
        }

        return $usages->get();
    }

    /**
     * @param  \Err0r\Larasub\Models\Subscription  $subscription
     *
     * @throws \InvalidArgumentException
     */
    public function totalFeatureUsageInPeriod($subscription, string $slug): float
    {
        $usages = $this->featureUsageInPeriod($subscription, $slug);

        return $usages->sum('value');
    }

    /**
     * Get the next time a feature will be available for use
     *
     * @param  \Err0r\Larasub\Models\Subscription  $subscription
     * @return \Carbon\Carbon|bool|null Returns:
     *                                  - `null`: Feature is unlimited (no usage restrictions)
     *                                  - `false`: Feature is not resettable (one-time use)
     *                                  - `Carbon`: Next time the feature will reset
     *
     * @throws \InvalidArgumentException
     */
    public function nextAvailableFeatureUsageInPeriod($subscription, string $slug)
    {
        $planFeature = $this->validateSubscriptionFeature($subscription, $slug);

        // Unlimited features are always available
        if ($planFeature->isUnlimited()) {
            return null;
        }

        // Non-resettable features
        if ($planFeature->reset_period === null || $planFeature->reset_period_type === null) {
            return false;
        }

        $usages = $this->featureUsageInPeriod($subscription, $slug);
        if ($usages->isEmpty()) {
            return now();
        }

        $oldestUsage = $usages->min('created_at');
        $resetMinutes = PeriodService::getMinutes(
            $planFeature->reset_period,
            $planFeature->reset_period_type
        );

        return $oldestUsage->addMinutes($resetMinutes);
    }

    /**
     * Get the next time a feature will be available for use
     *
     * @param  iterable<\Err0r\Larasub\Models\Subscription>  $subscriptions
     * @return \Carbon\Carbon|bool|null
     *
     * @throws \InvalidArgumentException
     */
    public function nextAvailableFeatureUsageBySubscriptions(iterable $subscriptions, string $slug)
    {
        $subscriptions = collect($subscriptions);

        if ($subscriptions->isEmpty()) {
            return false;
        }

        $nextUsages = $subscriptions->map(fn ($subscription) => $this->nextAvailableFeatureUsageInPeriod($subscription, $slug));

        if ($nextUsages->containsStrict(null)) {
            return null;
        }

        return $nextUsages->filter()->sort()->first() ?? false;
    }

    /**
     * @param  \Err0r\Larasub\Traits\Subscribable  $subscriber
     * @param  \Err0r\Larasub\Models\Plan  $plan
     * @param  \Err0r\Larasub\Models\Subscription|null  $renewedFrom
     * @return \Err0r\Larasub\Models\Subscription
     */
    public function subscribe($subscriber, $plan, ?Carbon $startAt = null, ?Carbon $endAt = null, bool $pending = false, $renewedFrom = null)
    {
        $startAt ??= now();

        if ($pending) {
            $startAt = null;
        }

        if ($startAt !== null && $endAt === null && $plan->reset_period !== null && $plan->reset_period_type !== null) {
            $endAt = PlanService::getPlanEndAt($plan, $startAt);
        }

        $subscription = new (config('larasub.models.subscription'))([
            'plan_id' => $plan->id,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        if ($renewedFrom) {
            $subscription->renewed_from_id = $renewedFrom->id;
        }

        /** @var \Err0r\Larasub\Models\Subscription|bool */
        $subscription = $subscriber->subscriptions()->save($subscription);

        if (! $subscription) {
            throw new \RuntimeException('Failed to create subscription');
        }

        return $subscription;
    }

    /**
     * Renew a subscription.
     *
     * @param  \Err0r\Larasub\Models\Subscription  $subscription
     * @return \Err0r\Larasub\Models\Subscription
     */
    public function renew($subscription, ?Carbon $startAt = null)
    {
        /** @var \Err0r\Larasub\Traits\Subscribable */
        $subscriber = $subscription->subscriber;

        return $this->subscribe($subscriber, $subscription->plan, renewedFrom: $subscription);
    }
}
