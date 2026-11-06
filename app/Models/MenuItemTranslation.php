<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItemTranslation extends Model
{
    protected $table = 'menu_item_translations';

    protected $fillable = ['menu_item_id', 'locale', 'title', 'custom_url'];

    public function menuitem()
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id', 'id');
    }
}
