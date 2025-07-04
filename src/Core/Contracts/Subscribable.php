<?php

namespace Err0r\Larasub\Core\Contracts;

use Err0r\Larasub\Core\Models\Plan;
use Err0r\Larasub\Core\Models\Subscription;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Subscribable
{
    /**
     * Get all subscriptions for the entity.
     */
    public function subscriptions(): MorphMany;

    /**
     * Get the active subscription.
     */
    public function subscription(): ?Subscription;

    /**
     * Check if the entity has any active subscription.
     */
    public function hasSubscription(): bool;

    /**
     * Check if the entity is subscribed to a specific plan.
     */
    public function subscribedTo(string|Plan $plan): bool;

    /**
     * Subscribe the entity to a plan.
     */
    public function subscribe(Plan $plan, array $options = []): Subscription;
}