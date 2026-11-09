<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $database = $this->databaseNameBeforeLaravelBoots();

        if ($database === 'cms_workspace' && getenv('ALLOW_DEV_DB_TRANSACTIONAL_TESTS') !== '1') {
            throw new RuntimeException(
                'SAFETY STOP: PHPUnit is pointing at the development database cms_workspace. '.
                'Do not run PHPUnit/RefreshDatabase on dev data. Use a dedicated test database, '.
                'or verify the feature manually in the browser against dev data. '.
                'Only tests that wrap every change in DB::beginTransaction()/rollBack() may opt in '.
                'with ALLOW_DEV_DB_TRANSACTIONAL_TESTS=1.'
            );
        }

        parent::setUp();
    }

    private function databaseNameBeforeLaravelBoots(): string
    {
        $fromProcess = getenv('DB_DATABASE');
        if (is_string($fromProcess) && trim($fromProcess) !== '') {
            return trim($fromProcess, " \t\n\r\0\x0B\"'");
        }

        $env = dirname(__DIR__).DIRECTORY_SEPARATOR.'.env';
        if (! is_file($env)) {
            return '';
        }

        foreach (file($env, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (preg_match('/^\s*DB_DATABASE\s*=\s*(.*?)\s*$/', $line, $matches) === 1) {
                return trim($matches[1], " \t\n\r\0\x0B\"'");
            }
        }

        return '';
    }
}
