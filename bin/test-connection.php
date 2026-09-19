<?php

declare(strict_types=1);

use App\Database\DatabaseConnection;

// Load Composer's PSR-4 autoloader.
require_once __DIR__ . '/../vendor/autoload.php';

// Load the database configuration.
$config = require __DIR__ . '/../config/database.php';

try {
    // Create the PDO connection using the dedicated database class.
    $pdo = DatabaseConnection::create($config);

    // Execute a lightweight query to verify that the connection works.
    $pdo->query('SELECT 1');

    echo "Database connection successful." . PHP_EOL;
} catch (Throwable $exception) {
    // Show the main application error.
    fwrite(
        STDERR,
        "Database connection failed: {$exception->getMessage()}" . PHP_EOL
    );

    // Show the original PDO error while debugging locally.
    if ($exception->getPrevious() !== null) {
        fwrite(
            STDERR,
            "PDO error: {$exception->getPrevious()->getMessage()}" . PHP_EOL
        );
    }

    exit(1);
}