<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

/**
 * Registration model: all SQL for the `registrations` table lives here.
 *
 * Seats taken = pending + confirmed (same rule as the class diagram).
 * First version WITHOUT a waiting list: a full event refuses the registration.
 * Sprint 3 moves register()/cancel() into RegistrationService and adds the waiting list.
 *
 * Lock order, always the same to avoid deadlocks: the EVENT row first, then the registration row.
 * "Past" is decided by MySQL (NOW()), so PHP and MySQL can never disagree about the time zone.
 */
class Registration
{
    private const COLUMNS = 'id, user_id, event_id, status, registered_at, updated_at';

    /** Statuses for which a cancellation is still possible. */
    private const CANCELLABLE = ['pending', 'confirmed', 'waitlisted'];

    // ------------------------------------------------------------------
    // Display helpers
    // ------------------------------------------------------------------

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending'    => 'En attente de validation',
            'confirmed'  => 'Confirmée',
            'waitlisted' => "Liste d'attente",
            'cancelled'  => 'Annulée',
            'rejected'   => 'Refusée',
            default      => $status,
        };
    }

    /** Can the owner still cancel a registration with this status? */
    public static function canCancel(string $status, bool $eventIsPast): bool
    {
        return !$eventIsPast && in_array($status, self::CANCELLABLE, true);
    }

    // ------------------------------------------------------------------
    // Queries
    // ------------------------------------------------------------------

    /** All registrations of a user, newest event first, with event and venue info. */
    public static function findByUser(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT r.id, r.status, r.registered_at, r.event_id,
                    e.title, e.event_date, e.event_time,
                    v.name AS venue_name,
                    (TIMESTAMP(e.event_date, e.event_time) <= NOW()) AS is_past
             FROM registrations r
             JOIN events e ON e.id = r.event_id
             JOIN venues v ON v.id = e.venue_id
             WHERE r.user_id = ?
             ORDER BY e.event_date DESC, e.event_time DESC, r.id DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** The registration of this user for this event, or null. */
    public static function findByUserAndEvent(int $userId, int $eventId): ?array
    {
        $stmt = db()->prepare(
            'SELECT ' . self::COLUMNS . ' FROM registrations WHERE user_id = ? AND event_id = ?'
        );
        $stmt->execute([$userId, $eventId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare('SELECT ' . self::COLUMNS . ' FROM registrations WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function countConfirmed(int $eventId): int
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM registrations WHERE event_id = ? AND status = 'confirmed'"
        );
        $stmt->execute([$eventId]);
        return (int)$stmt->fetchColumn();
    }

    /** Seats taken = pending + confirmed. */
    public static function seatsTaken(int $eventId): int
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM registrations WHERE event_id = ? AND status IN ('pending', 'confirmed')"
        );
        $stmt->execute([$eventId]);
        return (int)$stmt->fetchColumn();
    }

    /** True once the event date + time have passed (also true if the event does not exist). */
    public static function eventIsPast(int $eventId): bool
    {
        $stmt = db()->prepare(
            'SELECT (TIMESTAMP(event_date, event_time) <= NOW()) FROM events WHERE id = ?'
        );
        $stmt->execute([$eventId]);
        $value = $stmt->fetchColumn();
        return $value === false ? true : (int)$value === 1;
    }

    // ------------------------------------------------------------------
    // Actions. Each returns ['ok' => bool, 'code' => int, 'message' => string, 'event_id' => ?int]
    // ------------------------------------------------------------------

    /**
     * Registers a user to an event (status 'pending', to be validated by the organizer).
     * Rules: the event exists and is not past; a user has ONE row per event (UNIQUE);
     * a cancelled registration is reused; a rejected one cannot be redone by the user;
     * a full event refuses (no waiting list yet).
     */
    public static function register(int $userId, int $eventId): array
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Lock the event row: two people cannot take the last seat at the same time.
            $stmt = $pdo->prepare(
                'SELECT max_capacity, (TIMESTAMP(event_date, event_time) <= NOW()) AS is_past
                 FROM events WHERE id = ? FOR UPDATE'
            );
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();
            if ($event === false) {
                return self::abort($pdo, 404, "Cet événement n'existe pas.");
            }
            if ((int)$event['is_past'] === 1) {
                return self::abort($pdo, 409, 'Cet événement est passé : les inscriptions sont fermées.', $eventId);
            }

            $stmt = $pdo->prepare(
                'SELECT id, status FROM registrations WHERE user_id = ? AND event_id = ? FOR UPDATE'
            );
            $stmt->execute([$userId, $eventId]);
            $existing = $stmt->fetch();

            if ($existing !== false && $existing['status'] === 'rejected') {
                return self::abort($pdo, 409, "Votre inscription a été refusée par l'organisateur.", $eventId);
            }
            if ($existing !== false && $existing['status'] !== 'cancelled') {
                return self::abort($pdo, 409, 'Vous êtes déjà inscrit à cet événement.', $eventId);
            }

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM registrations WHERE event_id = ? AND status IN ('pending', 'confirmed')"
            );
            $stmt->execute([$eventId]);
            if ((int)$stmt->fetchColumn() >= (int)$event['max_capacity']) {
                return self::abort($pdo, 409, 'Cet événement est complet.', $eventId);
            }

            if ($existing !== false) {
                // cancelled before: reuse the row (UNIQUE user_id + event_id), back of the line
                $stmt = $pdo->prepare(
                    "UPDATE registrations SET status = 'pending', registered_at = NOW() WHERE id = ?"
                );
                $stmt->execute([(int)$existing['id']]);
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO registrations (user_id, event_id, status) VALUES (?, ?, 'pending')"
                );
                $stmt->execute([$userId, $eventId]);
            }

            $pdo->commit();
            return self::ok("Inscription enregistrée : elle sera confirmée par l'organisateur.", $eventId);
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (($ex->errorInfo[1] ?? 0) === 1062) {      // duplicate (user_id, event_id)
                return self::fail(409, 'Vous êtes déjà inscrit à cet événement.', $eventId);
            }
            throw $ex;
        }
    }

    /**
     * Cancels a registration. Only the OWNER can cancel, only while it is pending, confirmed
     * or waitlisted, and not once the event has passed (attendance and statistics depend on it).
     * No promotion from the waiting list yet: that is Sprint 3.
     */
    public static function cancel(int $userId, int $registrationId): array
    {
        $reg = self::findById($registrationId);
        if ($reg === null) {
            return self::fail(404, 'Inscription introuvable.');
        }
        $eventId = (int)$reg['event_id'];
        if ((int)$reg['user_id'] !== $userId) {
            return self::fail(403, 'Vous ne pouvez annuler que votre propre inscription.', $eventId);
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            // same lock order as register(): event first, then the registration
            $stmt = $pdo->prepare(
                'SELECT (TIMESTAMP(event_date, event_time) <= NOW()) AS is_past
                 FROM events WHERE id = ? FOR UPDATE'
            );
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();

            $stmt = $pdo->prepare('SELECT user_id, status FROM registrations WHERE id = ? FOR UPDATE');
            $stmt->execute([$registrationId]);
            $locked = $stmt->fetch();

            if ($event === false || $locked === false || (int)$locked['user_id'] !== $userId) {
                return self::abort($pdo, 404, 'Inscription introuvable.');
            }
            if (!in_array($locked['status'], self::CANCELLABLE, true)) {
                return self::abort($pdo, 409, 'Cette inscription ne peut plus être annulée.', $eventId);
            }
            if ((int)$event['is_past'] === 1) {
                return self::abort($pdo, 409, "L'événement est passé : l'annulation n'est plus possible.", $eventId);
            }

            $stmt = $pdo->prepare("UPDATE registrations SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$registrationId]);

            $pdo->commit();
            return self::ok('Votre inscription a été annulée.', $eventId);
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $ex;
        }
    }

    // ------------------------------------------------------------------
    // Result helpers
    // ------------------------------------------------------------------

    private static function ok(string $message, int $eventId): array
    {
        return ['ok' => true, 'code' => 200, 'message' => $message, 'event_id' => $eventId];
    }

    private static function fail(int $code, string $message, ?int $eventId = null): array
    {
        return ['ok' => false, 'code' => $code, 'message' => $message, 'event_id' => $eventId];
    }

    /** Rolls the open transaction back and returns a failure. */
    private static function abort(PDO $pdo, int $code, string $message, ?int $eventId = null): array
    {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return self::fail($code, $message, $eventId);
    }
}
