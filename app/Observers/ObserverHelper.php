<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class ObserverHelper
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        activity()
            ->performedOn($model)
            ->log('Thêm mới ' . $this->name);
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated($model): void
    {
        activity()
            ->performedOn($model)
            ->log('Cập nhật ' . $this->name);
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted($model): void
    {
        activity()
            ->performedOn($model)
            ->log('Xóa ' . $this->name);
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored($model): void
    {
        activity()
            ->performedOn($model)
            ->log('Phục hồi ' . $this->name . ' bị xóa');
    }

    /**
     * Handle the model "force deleted" event.
     */
    public function forceDeleted($model): void
    {
        activity()
            ->performedOn($model)
            ->log('Xóa ' . $this->name . ' trong cơ sở dữ liệu');
    }
}
