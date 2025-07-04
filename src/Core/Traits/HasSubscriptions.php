<?php

namespace Err0r\Larasub\Core\Traits;

use Carbon\Carbon;
use Err0r\Larasub\Core\Models\Plan;
use Err0r\Larasub\Core\Models\Subscription;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSubscriptions
{
    /**
     * Get all subscriptions for this model
     */
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscriber')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Get active subscriptions
     */
    public function activeSubscriptions(): MorphMany
    {
        return $this->subscriptions()->active();
    }

    /**
     * Get the current active subscription
     */
    public function subscription(): ?Subscription
    {
        return $this->activeSubscriptions()->first();
    }

    /**
     * Check if the model has any active subscription
     */
    public function hasSubscription(): bool
    {
        return $this->activeSubscriptions()->exists();
    }

    /**
     * Check if the model is subscribed to a specific plan
     */
    public function subscribedTo($plan): bool
    {
        $planId = $plan instanceof Plan ? $plan->id : $plan;
        
        return $this->activeSubscriptions()
                    ->where('plan_id', $planId)
                    ->exists();
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe($plan, array $options = []): Subscription
    {
        $plan = $plan instanceof Plan ? $plan : Plan::findOrFail($plan);
        
        // Cancel any existing active subscriptions if requested
        if ($options['cancel_existing'] ?? false) {
            $this->activeSubscriptions()->each->cancel(true);
        }
        
        $subscription = $this->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => $options['status'] ?? 'pending',
            'starts_at' => $options['starts_at'] ?? now(),
            'ends_at' => $options['ends_at'] ?? $plan->calculateEndDate($options['starts_at'] ?? now()),
            'trial_ends_at' => $options['trial_ends_at'] ?? null,
            'metadata' => $options['metadata'] ?? [],
        ]);
        
        // Auto-activate if not pending
        if ($subscription->status !== 'pending') {
            $subscription->activate();
        }
        
        return $subscription;
    }

    /**
     * Create a subscription starting in the future
     */
    public function subscribeFrom(Carbon $startsAt, $plan, array $options = []): Subscription
    {
        $options['starts_at'] = $startsAt;
        return $this->subscribe($plan, $options);
    }

    /**
     * Subscribe with a trial period
     */
    public function subscribeWithTrial($plan, int $trialDays, array $options = []): Subscription
    {
        $options['trial_ends_at'] = now()->addDays($trialDays);
        return $this->subscribe($plan, $options);
    }

    /**
     * Check if on trial for any subscription
     */
    public function onTrial(): bool
    {
        return $this->subscriptions()
                    ->active()
                    ->whereNotNull('trial_ends_at')
                    ->where('trial_ends_at', '>', now())
                    ->exists();
    }

    /**
     * Check if on trial for a specific plan
     */
    public function onTrialFor($plan): bool
    {
        $planId = $plan instanceof Plan ? $plan->id : $plan;
        
        return $this->subscriptions()
                    ->active()
                    ->where('plan_id', $planId)
                    ->whereNotNull('trial_ends_at')
                    ->where('trial_ends_at', '>', now())
                    ->exists();
    }

    /**
     * Cancel all active subscriptions
     */
    public function cancelSubscriptions(bool $immediately = false): void
    {
        $this->activeSubscriptions()->each(function ($subscription) use ($immediately) {
            $subscription->cancel($immediately);
        });
    }

    /**
     * Get subscription for a specific plan
     */
    public function subscriptionFor($plan): ?Subscription
    {
        $planId = $plan instanceof Plan ? $plan->id : $plan;
        
        return $this->activeSubscriptions()
                    ->where('plan_id', $planId)
                    ->first();
    }

    /**
     * Switch to a different plan
     */
    public function switchTo($plan, array $options = []): Subscription
    {
        // Cancel current subscription at period end
        if ($current = $this->subscription()) {
            $current->cancel();
            
            // Set new subscription to start when current ends
            if (!isset($options['starts_at']) && $current->ends_at) {
                $options['starts_at'] = $current->ends_at;
            }
        }
        
        return $this->subscribe($plan, $options);
    }

    /**
     * Immediately switch to a different plan
     */
    public function switchToNow($plan, array $options = []): Subscription
    {
        $options['cancel_existing'] = true;
        return $this->subscribe($plan, $options);
    }
}