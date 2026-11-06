<?php

return [

  'paths' => [
    base_path() . '/resources/views',
    base_path() . '/' . env('VITE_THEME_PATH', 'resources/themes') . '/' . env('VITE_THEME_NAME', 'materialize'),
  ],

  'theme_path' => env('VITE_THEME_PATH', 'resources/themes'),

  'theme' => env('VITE_THEME_NAME', 'materialize'),

  'strict' => env('VITE_STRICT', true),
 

];
