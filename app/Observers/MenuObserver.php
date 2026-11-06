<?php

namespace App\Observers;

use App\Models\Menu;

class MenuObserver
{
    private ObserverHelper $observerHelper;

    public function __construct()
    {
        $this->observerHelper = new ObserverHelper('Menu');
    }

    /**
     * Handle the Menu "created" event.
     */
    public function created(Menu $menu): void
    {
        $this->observerHelper->created($menu);
    }

    /**
     * Handle the Menu "updated" event.
     */
    public function updated(Menu $menu): void
    {
        $this->observerHelper->updated($menu);
        // if ($menu->isDirty('name')) {
        //     $previousName = $menu->getOriginal('name');
        //     $currentName = $menu->name;
        //     activity()
        //         ->performedOn($menu)
        //         ->log('Đổi tên từ ' . $previousName . ' thành ' . $currentName);
        // }
    }

    /**
     * Handle the Menu "deleted" event.
     */
    public function deleted(Menu $menu): void
    {
        $this->observerHelper->deleted($menu);
    }

    /**
     * Handle the Menu "restored" event.
     */
    public function restored(Menu $menu): void
    {
        $this->observerHelper->restored($menu);
    }

    /**
     * Handle the Menu "force deleted" event.
     */
    public function forceDeleted(Menu $menu): void
    {
        $this->observerHelper->forceDeleted($menu);
    }
}
