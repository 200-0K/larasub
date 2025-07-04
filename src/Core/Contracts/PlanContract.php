<?php

namespace Err0r\Larasub\Core\Contracts;

use Carbon\Carbon;

interface PlanContract
{
    /**
     * Check if the plan is free.
     */
    public function isFree(): bool;

    /**
     * Check if the plan is active.
     */
    public function isActive(): bool;

    /**
     * Calculate the end date for a subscription starting from the given date.
     */
    public function calculateEndDate(?Carbon $startDate = null): Carbon;

    /**
     * Get the plan's display price with currency.
     */
    public function getFormattedPrice(): string;

    /**
     * Get the plan's period in human readable format.
     */
    public function getHumanReadablePeriod(): string;
}