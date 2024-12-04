<?php

namespace Err0r\Larasub\Services;

final class SubscriptionService
{
    /**
     * Renew a subscription.
     *
     * @param  \Err0r\Larasub\Models\Subscription  $subscription
     * @return \Err0r\Larasub\Models\Subscription
     */
    public function renew($subscription)
    {
        /** @var \Err0r\Larasub\Traits\Subscribable */
        $subscriber = $subscription->subscriber;

        return $subscriber->subscribe($subscription->plan);
    }
}
