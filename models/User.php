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
    /** All users for the admin page (never includes password_hash). */
    public static function findAll(): array
    {
        return db()
            ->query('SELECT ' . self::PUBLIC_COLUMNS . ' FROM users ORDER BY created_at, id')
            ->fetchAll();
    }

    /** True if another account (not $exceptId) already uses this e-mail. */
    public static function emailTakenByOther(string $email, int $exceptId): bool
    {
        $stmt = db()->prepare('SELECT 1 FROM users WHERE email = ? AND id <> ?');
        $stmt->execute([strtolower(trim($email)), $exceptId]);
        return $stmt->fetchColumn() !== false;
    }

    public static function updateProfile(int $id, string $name, string $email, ?string $phone): void
    {
        $stmt = db()->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
        $stmt->execute([trim($name), strtolower(trim($email)), $phone, $id]);
    }

    /** The stored hash, only for checking the current password. */
    public static function passwordHashOf(int $id): ?string
    {
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $hash = $stmt->fetchColumn();
        return $hash === false ? null : (string)$hash;
    }

    public static function setPassword(int $id, string $password): void
    {
        $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public static function setRole(int $id, string $role): void
    {
        if (!in_array($role, ['admin', 'organizer', 'participant'], true)) {
            throw new InvalidArgumentException('Rôle invalide.');
        }
        $stmt = db()->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $id]);
    }

    /**
     * Deletes an account (registrations are removed by the database cascade).
     * Throws PDOException 1451 if the user still organizes events: the caller handles it.
     */
    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }
}
