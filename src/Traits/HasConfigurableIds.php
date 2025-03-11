<?php

namespace Err0r\Larasub\Traits;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

trait HasConfigurableIds
{
    use HasUuids {
        newUniqueId as protected newBaseUniqueId;
    }

    /**
     * Initialize the trait.
     *
     * @return void
     */
    public function initializeHasConfigurableIds()
    {
        $this->usesUniqueIds = $this->usesUuids();
    }

    /**
     * Get a new unique ID for the model.
     */
    public function newUniqueId()
    {
        return $this->usesUuids() ? $this->newBaseUniqueId() : null;
    }

    /**
     * Determine if the model uses UUIDs.
     */
    abstract protected function usesUuids(): bool;
}
