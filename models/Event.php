<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/Venue.php';

/** Event model: all SQL for the `events` table lives here. */
class Event
{
    /** Liste des événements (page events.php), avec le nom du lieu. */
    public static function findAll(): array
    {
        return db()->query(
            'SELECT e.id, e.title, e.event_date, e.event_time, e.max_capacity,
                    v.name AS venue_name
             FROM events e
             JOIN venues v ON v.id = e.venue_id
             ORDER BY e.event_date, e.event_time'
        )->fetchAll();
    }

    /** Un événement avec lieu et organisateur, ou null s'il n'existe pas. */
    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT e.*, v.name AS venue_name, v.address AS venue_address,
                    u.name AS organizer_name
             FROM events e
             JOIN venues v ON v.id = e.venue_id
             JOIN users u  ON u.id = e.organizer_id
             WHERE e.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Le lieu est-il déjà pris à cette date et cette heure ? */
    public static function existsAt(int $venueId, string $date, string $time, ?int $excludeEventId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM events WHERE venue_id = ? AND event_date = ? AND event_time = ?';
        $params = [$venueId, $date, $time];

        if ($excludeEventId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeEventId;
        }

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Valide les données du formulaire (règles du diagramme de séquence).
     * Retourne [] si tout est bon, sinon ['champ' => 'message'].
     */
    public static function validate(array $data, ?int $excludeEventId = null): array
    {
        $errors = [];

        $title = trim((string)($data['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > 150) {
            $errors['title'] = 'Le titre est obligatoire (150 caractères maximum).';
        }

        $description = trim((string)($data['description'] ?? ''));
        if (mb_strlen($description) > 2000) {
            $errors['description'] = 'La description est trop longue (2000 caractères maximum).';
        }

        $date = (string)($data['event_date'] ?? '');
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($d === false || $d->format('Y-m-d') !== $date) {
            $errors['event_date'] = 'La date est invalide.';
        } elseif ($date < date('Y-m-d')) {
            $errors['event_date'] = 'La date doit être dans le futur.';
        }

        $time = (string)($data['event_time'] ?? '');
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
            $errors['event_time'] = "L'heure est invalide.";
        }

        $capacity = filter_var($data['max_capacity'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
                if ($excludeEventId !== null && $capacity !== false) {
            // editing: never go below the seats already taken (pending + confirmed)
            $stmt = db()->prepare(
                "SELECT COUNT(*) FROM registrations WHERE event_id = ? AND status IN ('pending', 'confirmed')"
            );
            $stmt->execute([$excludeEventId]);
            $taken = (int)$stmt->fetchColumn();
            if ($capacity < $taken) {
                $errors['max_capacity'] = 'Capacité inférieure aux places déjà réservées (' . $taken . ').';
            }
        }
        if ($capacity === false) {
            $errors['max_capacity'] = 'Capacité minimale : 1.';
        }

        $venueId = filter_var($data['venue_id'] ?? null, FILTER_VALIDATE_INT);
        $venue = $venueId === false ? null : Venue::findById($venueId);
        if ($venue === null) {
            $errors['venue_id'] = "Le lieu n'existe pas.";
        } else {
            if ($capacity !== false && $capacity > (int)$venue['capacity']) {
                $errors['max_capacity'] = 'La capacité dépasse celle du lieu ('
                    . (int)$venue['capacity'] . ' places).';
            }
            if (!isset($errors['event_date']) && !isset($errors['event_time'])
                && self::existsAt($venueId, $date, $time, $excludeEventId)) {
                $errors['venue_id'] = 'Lieu déjà réservé à cette date et heure.';
            }
        }
        return $errors;
    }

    /** Vérifie si l'utilisateur peut modifier ou supprimer cet événement. */
    public static function canManage(array $event, ?array $user): bool
    {
        if ($user === null) {
            return false;
        }
        return $user['role'] === 'admin'
    || ($user['role'] === 'organizer' && (int)$event['organizer_id'] === (int)$user['id']);
    }

    /** Liste filtrée des événements (ex. ['organizer_id' => 3]), avec le nom du lieu. */
    public static function search(array $filters = []): array
    {
        $sql = 'SELECT e.id, e.title, e.event_date, e.event_time, e.max_capacity,
                       v.name AS venue_name
                FROM events e
                JOIN venues v ON v.id = e.venue_id';
        $params = [];

        if (isset($filters['organizer_id'])) {
            $sql .= ' WHERE e.organizer_id = ?';
            $params[] = (int)$filters['organizer_id'];
        }

        $sql .= ' ORDER BY e.event_date, e.event_time';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Insère un événement et retourne son id. Appelez validate() avant. */
    public static function create(array $data, int $organizerId): int
    {
        $description = trim((string)($data['description'] ?? ''));
        $stmt = db()->prepare(
            'INSERT INTO events
               (title, description, event_date, event_time, venue_id, max_capacity, organizer_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            trim((string)$data['title']),
            $description === '' ? null : $description,
            (string)$data['event_date'],
            (string)$data['event_time'],
            (int)$data['venue_id'],
            (int)$data['max_capacity'],
            $organizerId,
        ]);
        return (int)db()->lastInsertId();
    }

    /** Met à jour un événement existant. Appelez validate() avant. */
    public static function update(int $id, array $data): void
    {
        $description = trim((string)($data['description'] ?? ''));
        $stmt = db()->prepare(
            'UPDATE events
             SET title = ?, description = ?, event_date = ?, event_time = ?,
                 venue_id = ?, max_capacity = ?
             WHERE id = ?'
        );
        $stmt->execute([
            trim((string)$data['title']),
            $description === '' ? null : $description,
            (string)$data['event_date'],
            (string)$data['event_time'],
            (int)$data['venue_id'],
            (int)$data['max_capacity'],
            $id,
        ]);
    }

    /** Supprime un événement par son id. */
    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM events WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** Prochains événements (page d'accueil), avec le nom du lieu. */
    public static function upcoming(int $limit = 3): array
    {
        $stmt = db()->prepare(
            'SELECT e.id, e.title, e.description, e.event_date, e.event_time, v.name AS venue_name
         FROM events e
         JOIN venues v ON v.id = e.venue_id
         WHERE TIMESTAMP(e.event_date, e.event_time) >= NOW()
         ORDER BY e.event_date, e.event_time
         LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
