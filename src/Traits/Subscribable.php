<?php

namespace Err0r\Larasub\Traits;

use Carbon\Carbon;
use Err0r\Larasub\Facades\SubscriptionHelperService;
use Err0r\Larasub\Models\Plan;
use Err0r\Larasub\Models\PlanVersion;
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
     * Subscribe the user to a plan or plan version.
     *
     * @param  Plan|PlanVersion  $planOrVersion  Plan instance (uses current version) or specific PlanVersion
     * @param  Carbon|null  $startAt  When the subscription starts (null for immediate, null with pending=true for pending)
     * @param  Carbon|null  $endAt  When the subscription ends (null for auto-calculation based on plan)
     * @param  bool  $pending  Whether the subscription should start in pending state
     * @return Subscription
     *
     * @throws \InvalidArgumentException
     */
    public function subscribe($planOrVersion, ?Carbon $startAt = null, ?Carbon $endAt = null, bool $pending = false)
    {
        if (! ($planOrVersion instanceof Plan) && ! ($planOrVersion instanceof PlanVersion)) {
            throw new \InvalidArgumentException('The plan must be an instance of Plan or PlanVersion');
        }

        $subscription = SubscriptionHelperService::subscribe($this, $planOrVersion, $startAt, $endAt, $pending);

        return $subscription;
    }

    /**
     * Check if the user is subscribed to a plan or plan version.
     *
     * @param  Plan|PlanVersion|string  $planOrVersion  Plan instance, PlanVersion instance, Plan's ID/slug, or PlanVersion's ID
     */
    public function subscribed($planOrVersion): bool
    {
        $query = $this->subscriptions();

        if ($planOrVersion instanceof PlanVersion) {
            $query->wherePlanVersion($planOrVersion);
        } else {
            $query->wherePlan($planOrVersion);
        }

        return $query->where(fn ($q) => $q
            ->active()
            ->orWhere(fn ($q) => $q->pending())
        )
            ->exists();
    }
}
