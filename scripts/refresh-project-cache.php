<?php

// Local project selection: refresh generated caches without modifying business data.
try {
    require dirname(__DIR__) . '/vendor/autoload.php';
    $app = require dirname(__DIR__) . '/bootstrap/app.php';
    $app->instance('request', Illuminate\Http\Request::create('http://localhost/project-selection', 'POST'));
    $files = new Illuminate\Filesystem\Filesystem();
    $files->delete([$app->getCachedConfigPath(), $app->getCachedRoutesPath(), $app->bootstrapPath('cache/modules.php')]);
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    if (Illuminate\Support\Facades\Artisan::call('view:clear') !== 0) {
        throw new RuntimeException('Khong xoa duoc view cache.');
    }
    foreach (['twin_menu', 'cms_twin_menu'] as $key) {
        Illuminate\Support\Facades\Cache::forget($key);
    }
    echo 'Da lam moi cache cau hinh, route, module va menu.' . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
