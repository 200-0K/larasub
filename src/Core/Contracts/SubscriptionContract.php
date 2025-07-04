<?php

namespace Err0r\Larasub\Core\Contracts;

interface SubscriptionContract
{
    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool;

    /**
     * Check if the subscription is on trial.
     */
    public function onTrial(): bool;

    /**
     * Check if the subscription has been cancelled.
     */
    public function isCancelled(): bool;

    /**
     * Check if the subscription has expired.
     */
    public function isExpired(): bool;

    /**
     * Cancel the subscription.
     */
    public function cancel(bool $immediately = false): self;

    /**
     * Resume a cancelled subscription.
     */
    public function resume(): self;

    /**
     * Renew the subscription.
     */
    public function renew(): self;

    /**
     * Extend the subscription by a number of days.
     */
    public function extend(int $days): self;

    /**
     * Check if the subscription is ending soon.
     */
    public function endingSoon(int $days = 7): bool;
}