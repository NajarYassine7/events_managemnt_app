<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../models/Registration.php';

require_login();

// Registration and cancellation are POST only: a link (GET) never changes anything.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('events.php');
}
csrf_check();

$user   = current_user();
$userId = (int)$user['id'];
$action = (string)($_POST['action'] ?? '');

if ($action === 'register') {
    $eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);
    if ($eventId === false) {
        render_error(404, "Cet événement n'existe pas.");
    }

    $result = Registration::register($userId, $eventId);
    if ($result['code'] === 404) {
        render_error(404, $result['message']);
    }
    flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    redirect('event.php?id=' . $eventId);
}

if ($action === 'cancel') {
    $registrationId = filter_var($_POST['registration_id'] ?? null, FILTER_VALIDATE_INT);
    if ($registrationId === false) {
        render_error(404, 'Inscription introuvable.');
    }

    // Ownership is checked inside Registration::cancel(): you can only cancel YOUR registration.
    $result = Registration::cancel($userId, $registrationId);
    if (!$result['ok'] && in_array($result['code'], [403, 404], true)) {
        render_error($result['code'], $result['message']);
    }
    flash_set($result['ok'] ? 'success' : 'error', $result['message']);

    // Where to go back to: the event page or the list (whitelist, never a free URL).
    if (($_POST['return'] ?? '') === 'event' && $result['event_id'] !== null) {
        redirect('event.php?id=' . (int)$result['event_id']);
    }
    redirect('my_registrations.php');
}

render_error(400, 'Action inconnue.');
