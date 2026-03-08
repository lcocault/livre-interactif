<?php

namespace App;

use PDO;
use PDOException;

/**
 * Database connection singleton using PDO and PostgreSQL.
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    /**
     * Returns the shared PDO connection.
     * Creates it on first call.
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $cfg = APP_CONFIG['db'];
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $cfg['host'],
                $cfg['port'] ?? 5432,
                $cfg['name']
            );
            try {
                self::$instance = new PDO(
                    $dsn,
                    $cfg['user'],
                    $cfg['password'],
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
                self::$instance->exec("SET NAMES 'UTF8'");
            } catch (PDOException $e) {
                if (APP_CONFIG['app']['debug'] ?? false) {
                    throw $e;
                }
                // Generic message in production
                throw new \RuntimeException('Database connection failed.');
            }
        }

        return self::$instance;
    }

    /** For unit-testing: inject a mock connection. */
    public static function setConnection(PDO $pdo): void
    {
        self::$instance = $pdo;
    }

    /** Reset singleton (useful in tests). */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
