<?php

use Illuminate\Support\Facades\Cache;


if (! function_exists('options'))
{
    function options()
    {
        $cacheKey = 'App.Models.Option#autoload';
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $optionDataProvider = new \App\DataProviders\OptionDataProvider();
        $options = $optionDataProvider->getAutoloadOption();
        $values = [];
        foreach ($options as $option) {
            $values[$option->key] = $option->value;
        }

        Cache::put($cacheKey, $values);

        return $values;
    }
}

if (! function_exists('option'))
{
    function option($key, $default = null)
    {
        if (is_string($key)) {
            return options()[$key] ?? $default;
        } else {
            return options()[$key->value] ?? $default;
        }
    }
}
