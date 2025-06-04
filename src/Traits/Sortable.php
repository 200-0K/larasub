<?php

namespace Err0r\Larasub\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Sortable
{
    /**
     * Boot the sortable trait for the model.
     */
    public static function bootSortable(): void
    {
        static::addGlobalScope('sortable', function (Builder $builder) {
            $builder->orderBy('sort_order', 'asc');
        });
    }

    /**
     * Scope a query to order by sort_order.
     *
     * @param  Builder  $query
     * @param  string  $direction
     * @return Builder
     */
    public function scopeSorted($query, string $direction = 'asc')
    {
        return $query->reorder('sort_order', $direction);
    }

    /**
     * Scope a query without the sortable global scope.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeUnsorted($query)
    {
        return $query->withoutGlobalScope('sortable');
    }
}
