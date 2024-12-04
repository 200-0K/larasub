<?php

namespace Err0r\Larasub\Services;

use Carbon\Carbon;

final class SubscriptionService
{
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

        return $subscriber->subscribe($subscription->plan, $startAt);
    }
}
