<?php

declare(strict_types=1);

/**
 * Returns one shared PDO connection per request.
 * Usage: $stmt = db()->prepare('SELECT * FROM events WHERE id = ?');
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $file = __DIR__ . '/../config/database.local.php';
    if (!is_file($file)) {
        http_response_code(500);
        exit('Missing config/database.local.php: copy config/database.example.php to it.');
    }
    $cfg = require $file;

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'],
        $cfg['port'],
        $cfg['dbname'],
        $cfg['charset']
    );

    try {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements
        ]);
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Database connection failed. Check XAMPP MySQL and config/database.local.php.');
    }

    return $pdo;
}
