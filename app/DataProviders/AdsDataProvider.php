<?php

namespace App\DataProviders;

use App\Models\Advertisement;
use App\Models\Menu;
use App\Models\Option;

class AdsDataProvider
{
    public function paginate($filter, $pageSize = 20)
    {
        return Advertisement::where(function ($builder) use ($filter) {
                if ($filter) {
                    $builder->where('title', 'like', "%$filter%");
                }
            })
            ->paginate($pageSize);
    }

    public function findById($id) 
    {
        $ad = Advertisement::findOrFail($id);
        return $ad;
    }

    public function getAds(string $key)
    {
        $now = now();
        $options = Option::where('key', $key)->get();
        foreach($options as $option){
            if ($option && intval($option->value) > 0) {
                $ads = Advertisement::where('id', $option->value)
                ->whereDate('start_date', '<=', $now)
                ->whereDate('end_date', '>=', $now);
                if($ads){
                    return $ads;
                }
            }
        }
        
        return null;
    }
    public function all()
{
    return Advertisement::all();
}
}
