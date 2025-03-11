<?php

namespace Err0r\Larasub\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
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
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'event_type',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('larasub.tables.events.name'));
    }

    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }
}
