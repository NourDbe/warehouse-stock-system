<?php

declare(strict_types=1);

/**
 * Database configuration.
 *
 * Environment variables are used when available so that
 * sensitive credentials do not need to be committed to Git.
 */
return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_NAME') ?: 'warehouse_stock_system',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
];