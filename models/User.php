<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

class User
{
    /** Colonnes publiques sécurisées (jamais password_hash) */
    private const PUBLIC_COLUMNS = 'id, name, email, phone, role, created_at';

    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare('SELECT ' . self::PUBLIC_COLUMNS . ' FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Inclut password_hash : uniquement pour la vérification à la connexion */
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

    /** Crée un compte participant avec mot de passe haché */
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

    /** Liste tous les utilisateurs (requête préparée) */
    public static function list(): array
    {
        $stmt = db()->prepare('SELECT ' . self::PUBLIC_COLUMNS . ' FROM users ORDER BY created_at DESC, id DESC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Alias pour compatibilité avec admin_users.php */
    public static function findAll(): array
    {
        return self::list();
    }

    /** Vérifie si un autre compte utilise déjà cet e-mail */
    public static function emailTakenByOther(string $email, int $exceptId): bool
    {
        $stmt = db()->prepare('SELECT 1 FROM users WHERE email = ? AND id <> ?');
        $stmt->execute([strtolower(trim($email)), $exceptId]);
        return $stmt->fetchColumn() !== false;
    }

    /** Met à jour les informations d'un utilisateur */
    public static function update(int $id, string $name, string $email, ?string $phone = null): void
    {
        $stmt = db()->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
        $stmt->execute([trim($name), strtolower(trim($email)), $phone, $id]);
    }

    /** Alias pour compatibilité avec profile.php */
    public static function updateProfile(int $id, string $name, string $email, ?string $phone): void
    {
        self::update($id, $name, $email, $phone);
    }

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

    /** Modifie le rôle d'un utilisateur */
    public static function changeRole(int $id, string $role): void
    {
        if (!in_array($role, ['admin', 'organizer', 'participant'], true)) {
            throw new InvalidArgumentException('Rôle invalide.');
        }
        $stmt = db()->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $id]);
    }

    /** Alias pour compatibilité avec admin_users.php */
    public static function setRole(int $id, string $role): void
    {
        self::changeRole($id, $role);
    }

    /** Supprime un compte utilisateur */
    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }
}