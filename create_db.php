<?php

require __DIR__ . '/vendor/autoload.php';

try {
    $environment = Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
    $driver = $environment['DB_CONNECTION'] ?? 'mysql';

    if ($driver !== 'mysql') {
        echo "DB_CONNECTION is not mysql; no database created.";
        exit(0);
    }

    $database = $environment['DB_DATABASE'] ?? null;
    if (!$database) {
        throw new RuntimeException('DB_DATABASE is not configured in .env.');
    }

    $host = $environment['DB_HOST'] ?? '127.0.0.1';
    $port = $environment['DB_PORT'] ?? '3306';
    $username = $environment['DB_USERNAME'] ?? 'root';
    $password = $environment['DB_PASSWORD'] ?? '';

    $pdo = new PDO("mysql:host={$host};port={$port}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $database = str_replace('`', '``', $database);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created successfully or already exists.";
} catch (Throwable $e) {
    echo "DB Error: " . $e->getMessage();
    exit(1);
}
