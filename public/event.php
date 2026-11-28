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

    <div class="btn-row">
        <a class="btn-nav-outline" href="events.php">← Retour à la liste</a>
        <?php if (Event::canManage($event, current_user())): ?>
            <a class="btn-nav-primary" href="event_edit.php?id=<?= (int)$event['id'] ?>">Modifier</a>
            <form method="post" action="event_delete.php" class="inline-form js-confirm-delete"
                  data-title="<?= e($event['title']) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$event['id'] ?>">
                <button type="submit" class="btn-danger-outline">Supprimer</button>
            </form>
        <?php endif; ?>
    </div>

    <?php
    $box = __DIR__ . '/../includes/registration_box.php';   // fichier de C
    if (is_file($box)) { require $box; }
    ?>

    <?php if (Event::canManage($event, current_user())): ?>
        <?php require __DIR__ . '/../includes/confirm_delete.php'; ?>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>