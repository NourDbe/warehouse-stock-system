<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Creates and configures the PDO database connection.
 *
 * Keeping the PDO connection inside this class isolates
 * database configuration from the rest of the application.
 */
final class DatabaseConnection
{
    /**
     * Create a new PDO connection.
     *
     * @param array<string, string> $config Database configuration.
     *
     * @throws RuntimeException If the database connection fails.
     */
    public static function create(array $config): PDO
    {
        try {
            // Build the MySQL Data Source Name (DSN).
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'],
                $config['database']
            );

            // Create and configure the PDO connection.
            return new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    // Convert database errors into PDOExceptions.
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                    // Return query results as associative arrays by default.
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                    // Use native prepared statements when possible.
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            // Hide low-level connection details from the application layer.
            throw new RuntimeException(
                'Database connection failed.',
                0,
                $exception
            );
        }
    }
}