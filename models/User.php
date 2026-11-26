<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

class User
{
    /** Columns safe to keep in memory and show: never password_hash. */
    private const PUBLIC_COLUMNS = 'id, name, email, phone, role, created_at';

    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare('SELECT ' . self::PUBLIC_COLUMNS . ' FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Includes password_hash: only for the login check. */
    public static function findByEmail(string $email): ?array
    {
        $stmt = db()->prepare(
            'SELECT ' . self::PUBLIC_COLUMNS . ', password_hash FROM users WHERE email = ?'
        );
        $stmt->execute([strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function emailExists(string $email): bool
    {
        $stmt = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([strtolower(trim($email))]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Creates a PARTICIPANT account. The role is not a parameter on purpose:
     * sign up can never create an organizer or an admin.
     */
    public static function create(string $name, string $email, string $password): int
    {
        $stmt = db()->prepare(
            "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'participant')"
        );
        $stmt->execute([
            trim($name),
            strtolower(trim($email)),
            password_hash($password, PASSWORD_DEFAULT),
        ]);
        return (int)db()->lastInsertId();
    }
}
