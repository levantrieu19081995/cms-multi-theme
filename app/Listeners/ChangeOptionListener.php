<?php

namespace App\Listeners;

use App\Events\ChangeOption;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class ChangeOptionListener
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
    public function handle(ChangeOption $event): void
    {
        Cache::forget('options');

        activity()
            ->log('Thay đổi cài đặt chung');
    }
}
