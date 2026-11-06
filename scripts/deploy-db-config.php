<?php

declare(strict_types=1);

// Deployment-only helper. It writes a temporary MySQL client file without
// printing credentials. The caller must delete both output files immediately.
if ($argc !== 5) {
    fwrite(STDERR, "Usage: php deploy-db-config.php <app-path> <client-cnf> <database-name-file> <last-migration-file>\n");
    exit(2);
}

[$script, $appPath, $clientFile, $databaseFile, $lastMigrationFile] = $argv;
unset($script);

if (! is_file($appPath.'/artisan') || ! is_file($appPath.'/vendor/autoload.php')) {
    fwrite(STDERR, "Invalid Laravel application path.\n");
    exit(3);
}

chdir($appPath);
require $appPath.'/vendor/autoload.php';
$app = require $appPath.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$connection = config('database.connections.mysql');
$required = ['host', 'port', 'database', 'username', 'password'];
foreach ($required as $key) {
    if (! array_key_exists($key, $connection)) {
        fwrite(STDERR, "Missing MySQL configuration: {$key}.\n");
        exit(4);
    }
}

$escape = static fn (mixed $value): string => str_replace(
    ["\\", "\r", "\n"],
    ["\\\\", '', ''],
    (string) $value,
);

$contents = "[client]\n"
    .'host='.$escape($connection['host'])."\n"
    .'port='.$escape($connection['port'])."\n"
    .'user='.$escape($connection['username'])."\n"
    .'password='.$escape($connection['password'])."\n"
    ."default-character-set=utf8mb4\n";

$lastMigration = Illuminate\Support\Facades\Schema::hasTable('migrations')
    ? (string) (Illuminate\Support\Facades\DB::table('migrations')->max('migration') ?? '')
    : '';

if (file_put_contents($clientFile, $contents, LOCK_EX) === false
    || file_put_contents($databaseFile, $escape($connection['database']), LOCK_EX) === false
    || file_put_contents($lastMigrationFile, $escape($lastMigration), LOCK_EX) === false) {
    fwrite(STDERR, "Cannot write temporary database files.\n");
    exit(5);
}

chmod($clientFile, 0600);
chmod($databaseFile, 0600);
chmod($lastMigrationFile, 0600);

