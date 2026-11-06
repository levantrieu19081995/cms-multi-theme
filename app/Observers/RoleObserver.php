<?php

namespace App\Observers;

use App\Events\UpdateRole;
use Spatie\Permission\Models\Role;

class RoleObserver
{

    private ObserverHelper $observerHelper;

    public function __construct()
    {
        $this->observerHelper = new ObserverHelper('Vai trò');
    }

    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role): void
    {
        $this->observerHelper->created($role);
    }

    /**
     * Handle the Role "updated" event.
     */
    public function updated(Role $role): void
    {
        // $this->observerHelper->updated($role);
        // Update case will use event listener instead of observer
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role): void
    {
        $this->observerHelper->deleted($role);
    }

    /**
     * Handle the Role "restored" event.
     */
    public function restored(Role $role): void
    {
        $this->observerHelper->restored($role);
    }

    /**
     * Handle the Role "force deleted" event.
     */
    public function forceDeleted(Role $role): void
    {
        $this->observerHelper->forceDeleted($role);
    }
}
