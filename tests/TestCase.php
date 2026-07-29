<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\TestingDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    /**
     * Memblokir konfigurasi database tidak aman sebelum
     * Laravel dan trait RefreshDatabase dijalankan.
     */
    protected function setUp(): void
    {
        TestingDatabaseGuard::assertSafeProcessEnvironment();

        parent::setUp();

        TestingDatabaseGuard::assertSafeApplication(
            $this->app
        );
    }
}
