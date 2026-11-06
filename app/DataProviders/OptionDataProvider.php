<?php

namespace App\DataProviders;

use App\Models\Option;
use App\Enums\OptionEnum;
use App\Events\ChangeOption;
use Illuminate\Support\Facades\Cache;

class OptionDataProvider
{
    public function getOptionByKey($keys)
    {
        if (is_array($keys)) {
            if ($keys && $keys[0] instanceof OptionEnum) {
                $keyVals = [];
                foreach ($keys as $key) {
                    $keyVals[] = $key->value;
                }
                return Option::whereIn('key', $keyVals)->get();
            }

            return $this->getAllOption()->whereIn('key', $keys)->all();
        } else {
            return $this->getAllOption()->where('key', $keys)->first();
        }
    }

    public function getAllOption()
    {
        $cacheKey = 'options';
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $options = Option::all();
        Cache::put($cacheKey, $options, now()->addHours(4));

        return $options;
    }

    public function getAutoloadOption()
    {
        return Option::whereAutoload(1)->get();
    }

    public function save($key, $value, $autoload = true)
    {
        $model = Option::where('key', $key)->first();
        if (!$model) {
            $model = new Option();
            $model->key = $key;
            $model->autoload = $autoload;
        }
        $model->value = $value;
        $model->save();

        event(new ChangeOption([$model]));
    }

    public function create($key, $value, $autoload = true)
    {
        $model = new Option();
        $model->key = $key;
        $model->autoload = $autoload;
        $model->value = $value;
        $model->save();

        event(new ChangeOption([$model]));
    }
}
