<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../models/Event.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_error(405, 'Méthode non autorisée.');
}

csrf_check();

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$event = $id === false ? null : Event::findById($id);
if ($event === null) {
    render_error(404, "Cet événement n'existe pas.");
}
if (!Event::canManage($event, current_user())) {
    render_error(403, "Vous n'avez pas le droit de supprimer cet événement.");
}

Event::delete((int)$event['id']);
flash_set('success', 'Événement supprimé.');
redirect('my_events.php');
