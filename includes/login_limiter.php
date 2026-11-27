<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

const LOGIN_MAX_FAILURES   = 5;
const LOGIN_WINDOW_MINUTES = 15;

function login_key(string $email): string
{
    return substr(strtolower(trim($email)), 0, 150);   // fits the column
}

function login_client_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? 'unknown', 0, 45);
}

/** Seconds left before this email+IP may try again, or 0 if not blocked. */
function login_blocked_seconds(string $email): int
{
    // The 5th most recent failure: while it is still inside the window, login is blocked.
    $stmt = db()->prepare(
        'SELECT TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(attempted_at, INTERVAL ' . LOGIN_WINDOW_MINUTES . ' MINUTE)) AS remaining
         FROM login_attempts
         WHERE email = ? AND ip = ?
         ORDER BY attempted_at DESC, id DESC
         LIMIT 1 OFFSET ' . (LOGIN_MAX_FAILURES - 1)
    );
    $stmt->execute([login_key($email), login_client_ip()]);
    $row = $stmt->fetch();

    return ($row !== false && (int)$row['remaining'] > 0) ? (int)$row['remaining'] : 0;
}

/** Call after every failed login, also when the email does not exist. */
function login_record_failure(string $email): void
{
    db()->prepare('INSERT INTO login_attempts (email, ip) VALUES (?, ?)')
        ->execute([login_key($email), login_client_ip()]);

    db()->exec('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
}

/** Call after a successful login. */
function login_clear_failures(string $email): void
{
    db()->prepare('DELETE FROM login_attempts WHERE email = ? AND ip = ?')
        ->execute([login_key($email), login_client_ip()]);
}
