<?php

namespace Err0r\Larasub\Models;

use Err0r\Larasub\Traits\HasConfigurableIds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $event_type
 * @property string $eventable_type
 * @property string|int $eventable_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Event extends Model
{
    use HasConfigurableIds;
    use HasFactory;

    protected $fillable = [
        'event_type',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('larasub.tables.events.name'));
    }

    protected function usesUuids(): bool
    {
        return config('larasub.tables.events.uuid');
    }

    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }
}
