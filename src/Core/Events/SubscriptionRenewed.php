<?php

namespace Err0r\Larasub\Core\Events;

use Err0r\Larasub\Core\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionRenewed
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Subscription $subscription
    ) {}
}