<?php

namespace Err0r\Larasub\Core\Traits;

use Carbon\Carbon;
use Err0r\Larasub\Core\Contracts\Subscribable;
use Err0r\Larasub\Core\Models\Plan;
use Err0r\Larasub\Core\Models\Subscription;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSubscriptions
{
    /**
     * Get all subscriptions for the entity.
     */
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscriber')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get all active subscriptions.
     */
    public function activeSubscriptions(): MorphMany
    {
        return $this->subscriptions()->active();
    }

    /**
     * Get the current active subscription.
     */
    public function subscription(): ?Subscription
    {
        return $this->activeSubscriptions()->first();
    }

    /**
     * Check if the entity has any active subscription.
     */
    public function hasSubscription(): bool
    {
        return $this->activeSubscriptions()->exists();
    }

    /**
     * Check if the entity is subscribed to a specific plan.
     */
    public function subscribedTo(string|Plan $plan): bool
    {
        $planId = $plan instanceof Plan ? $plan->id : Plan::where('slug', $plan)->value('id');
        
        return $this->activeSubscriptions()
            ->where('plan_id', $planId)
            ->exists();
    }

    /**
     * Subscribe the entity to a plan.
     */
    public function subscribe(Plan $plan, array $options = []): Subscription
    {
        // Cancel any existing active subscription if switching
        if ($this->hasSubscription() && !($options['keep_existing'] ?? false)) {
            $this->subscription()->cancel();
        }

        return $this->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => $options['status'] ?? 'active',
            'starts_at' => $options['starts_at'] ?? now(),
            'ends_at' => $options['ends_at'] ?? $plan->calculateEndDate($options['starts_at'] ?? now()),
            'trial_ends_at' => $options['trial_ends_at'] ?? null,
            'metadata' => $options['metadata'] ?? [],
        ]);
    }

    /**
     * Subscribe starting from a specific date.
     */
    public function subscribeFrom($startDate, Plan $plan, array $options = []): Subscription
    {
        $options['starts_at'] = $startDate;
        return $this->subscribe($plan, $options);
    }

    /**
     * Subscribe with a trial period.
     */
    public function subscribeWithTrial(Plan $plan, int $trialDays, array $options = []): Subscription
    {
        $options['trial_ends_at'] = now()->addDays($trialDays);
        return $this->subscribe($plan, $options);
    }

    /**
     * Switch to a different plan.
     */
    public function switchTo(Plan $plan, bool $immediately = false): ?Subscription
    {
        $currentSubscription = $this->subscription();
        
        if (!$currentSubscription) {
            return $this->subscribe($plan);
        }

        if ($immediately) {
            return $this->switchToNow($plan);
        }

        // Schedule the switch at the end of the current period
        return $this->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'pending',
            'starts_at' => $currentSubscription->ends_at,
            'ends_at' => $plan->calculateEndDate($currentSubscription->ends_at),
            'metadata' => ['switched_from' => $currentSubscription->plan_id],
        ]);
    }

    /**
     * Switch to a different plan immediately.
     */
    public function switchToNow(Plan $plan): Subscription
    {
        $currentSubscription = $this->subscription();
        
        if ($currentSubscription) {
            $currentSubscription->cancel(true);
        }

        return $this->subscribe($plan);
    }

    /**
     * Check if the entity has ever subscribed.
     */
    public function hasEverSubscribed(): bool
    {
        return $this->subscriptions()->exists();
    }

    /**
     * Get the entity's latest subscription (active or not).
     */
    public function latestSubscription(): ?Subscription
    {
        return $this->subscriptions()->latest()->first();
    }

    /**
     * Cancel all active subscriptions.
     */
    public function cancelAllSubscriptions(bool $immediately = false): void
    {
        $this->activeSubscriptions()->each(function ($subscription) use ($immediately) {
            $subscription->cancel($immediately);
        });
    }

    /**
     * Check if the entity is on trial for any subscription.
     */
    public function onTrial(): bool
    {
        return $this->activeSubscriptions()
            ->get()
            ->contains(fn ($subscription) => $subscription->onTrial());
    }

    /**
     * Get subscriptions that are ending soon.
     */
    public function subscriptionsEndingSoon(int $days = 7)
    {
        return $this->activeSubscriptions()
            ->get()
            ->filter(fn ($subscription) => $subscription->endingSoon($days));
    }
}