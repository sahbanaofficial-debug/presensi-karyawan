<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Foundation\Application;
use RuntimeException;

final class TestingDatabaseGuard
{
    /**
     * Database development yang tidak boleh digunakan oleh test.
     *
     * @var list<string>
     */
    private const PROTECTED_DATABASES = [
        'presensi_karyawan',
    ];

    /**
     * Memeriksa environment proses sebelum aplikasi Laravel boot.
     *
     * Pemeriksaan ini harus dipanggil sebelum parent::setUp()
     * agar RefreshDatabase belum memiliki kesempatan menjalankan
     * migration atau menghapus tabel.
     */
    public static function assertSafeProcessEnvironment(): void
    {
        $environment = self::processValue(
            'APP_ENV'
        );

        if ($environment !== 'testing') {
            throw new RuntimeException(
                sprintf(
                    'Testing database guard blocked unsafe configuration: APP_ENV must be [testing], actual [%s].',
                    $environment === ''
                        ? '<empty>'
                        : $environment
                )
            );
        }

        $databaseUrl = self::processValue(
            'DB_URL'
        );

        if ($databaseUrl !== '') {
            throw new RuntimeException(
                'Testing database guard blocked unsafe configuration: DB_URL must be empty during tests.'
            );
        }

        self::assertSafeDefaultDatabase(
            connection: self::processValue(
                'DB_CONNECTION'
            ),
            database: self::processValue(
                'DB_DATABASE'
            ),
            source: 'process environment'
        );
    }

    /**
     * Memeriksa konfigurasi efektif setelah aplikasi boot.
     */
    public static function assertSafeApplication(
        Application $application
    ): void {
        if (! $application->environment('testing')) {
            throw new RuntimeException(
                sprintf(
                    'Testing database guard blocked unsafe application environment: [%s].',
                    $application->environment()
                )
            );
        }

        if ($application->configurationIsCached()) {
            throw new RuntimeException(
                'Testing database guard blocked cached configuration. Remove the configuration cache before running tests.'
            );
        }

        $configuration = $application->make(
            'config'
        );

        $defaultConnection = trim(
            (string) $configuration->get(
                'database.default',
                ''
            )
        );

        $connections = $configuration->get(
            'database.connections',
            []
        );

        if (
            $defaultConnection === ''
            || ! is_array($connections)
            || ! isset($connections[$defaultConnection])
            || ! is_array($connections[$defaultConnection])
        ) {
            throw new RuntimeException(
                'Testing database guard blocked invalid default database connection configuration.'
            );
        }

        foreach ($connections as $name => $connection) {
            if (! is_array($connection)) {
                continue;
            }

            $url = trim(
                (string) ($connection['url'] ?? '')
            );

            if ($url !== '') {
                throw new RuntimeException(
                    sprintf(
                        'Testing database guard blocked DB URL on connection [%s].',
                        (string) $name
                    )
                );
            }

            $database = trim(
                (string) ($connection['database'] ?? '')
            );

            if (self::isProtectedDatabase($database)) {
                throw new RuntimeException(
                    sprintf(
                        'Testing database guard blocked protected database [%s] on connection [%s].',
                        $database,
                        (string) $name
                    )
                );
            }
        }

        $defaultConfiguration =
            $connections[$defaultConnection];

        $driver = trim(
            strtolower(
                (string) (
                    $defaultConfiguration['driver']
                    ?? $defaultConnection
                )
            )
        );

        $configuredDatabase = trim(
            (string) (
                $defaultConfiguration['database']
                ?? ''
            )
        );

        self::assertSafeDefaultDatabase(
            connection: $driver,
            database: $configuredDatabase,
            source: 'Laravel configuration'
        );

        $databaseManager = $application->make(
            'db'
        );

        $actualDatabase = trim(
            (string) $databaseManager
                ->connection($defaultConnection)
                ->getDatabaseName()
        );

        self::assertSafeDefaultDatabase(
            connection: $driver,
            database: $actualDatabase,
            source: 'active database connection'
        );
    }

    private static function assertSafeDefaultDatabase(
        string $connection,
        string $database,
        string $source
    ): void {
        $connection = strtolower(
            trim($connection)
        );

        $database = trim($database);

        if (self::isProtectedDatabase($database)) {
            throw new RuntimeException(
                sprintf(
                    'Testing database guard blocked unsafe configuration: %s database [%s] is protected.',
                    $source,
                    $database
                )
            );
        }

        if ($connection === 'sqlite') {
            if ($database !== ':memory:') {
                throw new RuntimeException(
                    sprintf(
                        'Testing database guard blocked unsafe SQLite database from %s: expected [:memory:], actual [%s].',
                        $source,
                        $database === ''
                            ? '<empty>'
                            : $database
                    )
                );
            }

            return;
        }

        if (
            in_array(
                $connection,
                [
                    'mysql',
                    'mariadb',
                ],
                true
            )
        ) {
            if (
                preg_match(
                    '/^[A-Za-z0-9_]+_testing$/D',
                    $database
                ) !== 1
            ) {
                throw new RuntimeException(
                    sprintf(
                        'Testing database guard blocked unsafe SQL database from %s: database [%s] must end with [_testing].',
                        $source,
                        $database === ''
                            ? '<empty>'
                            : $database
                    )
                );
            }

            return;
        }

        throw new RuntimeException(
            sprintf(
                'Testing database guard blocked unsupported connection [%s] from %s.',
                $connection === ''
                    ? '<empty>'
                    : $connection,
                $source
            )
        );
    }

    private static function isProtectedDatabase(
        string $database
    ): bool {
        return in_array(
            strtolower(
                trim($database)
            ),
            self::PROTECTED_DATABASES,
            true
        );
    }

    private static function processValue(
        string $name
    ): string {
        $value = getenv($name);

        if ($value !== false) {
            return trim(
                (string) $value
            );
        }

        if (array_key_exists($name, $_ENV)) {
            return trim(
                (string) $_ENV[$name]
            );
        }

        if (array_key_exists($name, $_SERVER)) {
            return trim(
                (string) $_SERVER[$name]
            );
        }

        return '';
    }
}
