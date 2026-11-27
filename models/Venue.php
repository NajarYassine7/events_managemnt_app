<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

/**
 * Venue model: all SQL for the `venues` table lives here.
 * Controllers validate with Venue::validate(), then call create().
 */
class Venue
{
    private const COLUMNS = 'id, name, address, capacity, description';

    /** All venues, sorted by name. Also used by B to fill the venue dropdown. */
    public static function findAll(): array
    {
        return db()
            ->query('SELECT ' . self::COLUMNS . ' FROM venues ORDER BY name')
            ->fetchAll();
    }

    /** One venue, or null if the id does not exist. */
    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare('SELECT ' . self::COLUMNS . ' FROM venues WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Validates raw form data. Returns [] when valid,
     * otherwise ['field' => 'message (in French)'].
     */
    public static function validate(array $data): array
    {
        $errors = [];

        $name = trim((string)($data['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 150) {
            $errors['name'] = 'Le nom est obligatoire (150 caractères maximum).';
        }

        $address = trim((string)($data['address'] ?? ''));
        if ($address === '' || mb_strlen($address) > 255) {
            $errors['address'] = "L'adresse est obligatoire (255 caractères maximum).";
        }

        $capacity = filter_var($data['capacity'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 100000],
        ]);
        if ($capacity === false) {
            $errors['capacity'] = 'La capacité doit être un nombre entier supérieur à 0.';
        }

        $description = trim((string)($data['description'] ?? ''));
        if (mb_strlen($description) > 2000) {
            $errors['description'] = 'La description est trop longue (2000 caractères maximum).';
        }

        return $errors;
    }

    /** Inserts a venue and returns its new id. Call validate() first. */
    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO venues (name, address, capacity, description) VALUES (?, ?, ?, ?)'
        );
        $description = trim((string)($data['description'] ?? ''));
        $stmt->execute([
            trim((string)$data['name']),
            trim((string)$data['address']),
            (int)$data['capacity'],
            $description === '' ? null : $description,
        ]);
        return (int)db()->lastInsertId();
    }
}
