<?php

namespace Err0r\Larasub\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Err0r\Larasub\Core\Models\Plan createPlan(array $attributes)
 * @method static \Illuminate\Database\Eloquent\Collection getActivePlans()
 * @method static \Err0r\Larasub\Core\Models\Plan|null findPlanBySlug(string $slug)
 * 
 * @see \Err0r\Larasub\Core\LarasubManager
 */
class Larasub extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'larasub';
    }
}