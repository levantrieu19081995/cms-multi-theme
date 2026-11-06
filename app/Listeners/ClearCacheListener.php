<?php

namespace App\Listeners;

use App\Events\ClearCache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ClearCacheListener
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
    public function handle(ClearCache $event): void
    {
        activity()
            ->log('Xóa bộ nhớ đệm');
    }
}
