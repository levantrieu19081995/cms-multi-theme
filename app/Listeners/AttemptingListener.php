<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Attempting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AttemptingListener
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
    public function handle(Attempting $event): void
    {
        $message = 'Đăng nhập thất bại tài khoản ';
        if (isset($event->credentials['email'])) {
            activity()
                ->log($message . $event->credentials['email']);
        } else if (isset($event->credentials['username'])) {
            activity()
                ->log($message . $event->credentials['username']);
        }
    }
}
