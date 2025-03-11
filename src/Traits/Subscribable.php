<?php

namespace Err0r\Larasub\Traits;

use Carbon\Carbon;
use Err0r\Larasub\Facades\SubscriptionHelperService;
use Err0r\Larasub\Models\Plan;
use Err0r\Larasub\Models\Subscription;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Subscribable
{
    /**
     * @return MorphMany<Subscription, $this>
     */
    public function subscriptions(): MorphMany
    {
        /** @var class-string<Subscription> */
        $class = config('larasub.models.subscription');

        return $this->morphMany($class, 'subscriber');
    }

    /**
     * Subscribe the user to a plan.
     *
     * @param  Plan  $plan
     * @return Subscription
     *
     * @throws \InvalidArgumentException
     */
    public function subscribe($plan, ?Carbon $startAt = null, ?Carbon $endAt = null, bool $pending = false)
    {
        /** @var class-string<Plan> */
        $planClass = config('larasub.models.plan');

        if (! ($plan instanceof $planClass)) {
            throw new \InvalidArgumentException("The plan must be an instance of $planClass");
        }

        $subscription = SubscriptionHelperService::subscribe($this, $plan, $startAt, $endAt, $pending);

        return $subscription;
    }

    /**
     * Check if the user is subscribed to a plan.
     *
     * @param  Plan|string  $plan  Plan instance or Plan's ID or slug
     */
    public function subscribed($plan): bool
    {
        return $this
            ->subscriptions()
            ->wherePlan($plan)
            ->where(fn ($q) => $q
                ->active()
                ->orWhere(fn ($q) => $q->pending())
            )
            ->exists();
    }
}
