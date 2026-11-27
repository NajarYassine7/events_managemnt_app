<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../models/Event.php';

require_login();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$event = $id === false ? null : Event::findById($id);
if ($event === null) {
    render_error(404, "Cet événement n'existe pas.");
}

$pageTitle = $event['title'];
require __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <h1><?= e($event['title']) ?></h1>

    <div class="card">
        <p><strong>Date :</strong> <?= e($event['event_date']) ?></p>
        <p><strong>Heure :</strong> <?= e(substr((string)$event['event_time'], 0, 5)) ?></p>
        <p><strong>Lieu :</strong> <?= e($event['venue_name']) ?> (<?= e($event['venue_address']) ?>)</p>
        <p><strong>Capacité maximale :</strong> <?= (int)$event['max_capacity'] ?></p>
        <p><strong>Organisateur :</strong> <?= e($event['organizer_name']) ?></p>
        <p><?= nl2br(e($event['description'] ?? '')) ?></p>
    </div>

    <p><a href="events.php">← Retour à la liste</a></p>
    <!-- Sprint 2 : boutons Modifier / Supprimer (organisateur propriétaire ou admin) -->
    <!-- Sprint 3 (C) : bouton « S'inscrire » -->
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
