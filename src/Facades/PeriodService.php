<?php

namespace Err0r\Larasub\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Err0r\Larasub\Services\PeriodService
 */
class PeriodService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Err0r\Larasub\Services\PeriodService::class;
    }
}
