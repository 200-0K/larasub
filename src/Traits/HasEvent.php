<?php

namespace Err0r\Larasub\Traits;

use Err0r\Larasub\Models\Event;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasEvent
{
    public function events(): MorphMany
    {
        /** @var class-string<Event> */
        $class = config('larasub.models.event');

        return $this->morphMany($class, 'eventable');
    }

    public function scopeWhereEventType($query, string $eventType)
    {
        return $query->whereHas('events', function ($query) use ($eventType) {
            $query->where('event_type', $eventType);
        });
    }

    /**
     * Add an event to the model.
     *
     * @return Event
     */
    public function addEvent(string $eventType)
    {
        /** @var Event */
        return $this->events()->create([
            'event_type' => $eventType,
        ]);
    }
}
