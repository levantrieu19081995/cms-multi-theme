<?php

namespace App\Listeners;

use App\Events\RoleUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RoleUpdatedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(RoleUpdated $event): void
    {
        activity()
            ->performedOn($event->role)
            ->log('Cập nhật vai trò');
    }
}
