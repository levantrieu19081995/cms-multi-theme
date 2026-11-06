<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{

    private ObserverHelper $observerHelper;

    public function __construct()
    {
        $this->observerHelper = new ObserverHelper('Người dùng');
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $this->observerHelper->created($user);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $this->observerHelper->updated($user);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $this->observerHelper->deleted($user);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        $this->observerHelper->restored($user);
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        $this->observerHelper->forceDeleted($user);
    }
}
