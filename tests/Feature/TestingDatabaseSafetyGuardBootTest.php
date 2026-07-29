<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class TestingDatabaseSafetyGuardBootTest extends TestCase
{
    public function test_safe_testing_environment_can_boot_application(): void
    {
        $this->assertTrue(
            $this->app->environment('testing')
        );

        $this->assertFalse(
            $this->app->configurationIsCached()
        );

        $this->assertNotEmpty(
            config('database.default')
        );
    }
}
