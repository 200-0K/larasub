<?php

namespace Err0r\Larasub\Services;

use Carbon\Carbon;
use Err0r\Larasub\Facades\PeriodService;

final class PlanService
{
    /**
     * @param  \Err0r\Larasub\Models\PlanVersion  $planVersion
     * @param  Carbon  $startAt
     */
    public function getPlanEndAt($planVersion, $startAt): ?Carbon
    {
        $endAt = null;

        if ($planVersion->reset_period !== null && $planVersion->reset_period_type !== null) {
            $endAt = $startAt->copy()->addDays(PeriodService::getDays($planVersion->reset_period, $planVersion->reset_period_type));
        }

        return $endAt;
    }
}
