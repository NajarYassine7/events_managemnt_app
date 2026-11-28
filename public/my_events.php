<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../models/Event.php';

require_role(['organizer']);
$user = current_user();

// L'admin voit tous les événements ; l'organisateur seulement les siens.
$filters = $user['role'] === 'admin' ? [] : ['organizer_id' => (int)$user['id']];
$events = Event::search($filters);

$pageTitle = 'Mes événements';
require __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <h1><?= $user['role'] === 'admin' ? 'Tous les événements' : 'Mes événements' ?></h1>

    <div class="btn-row">
        <a class="btn-nav-primary" href="event_create.php">Créer un événement</a>
    </div>

    <?php if ($events === []): ?>
        <p>Vous n'avez encore créé aucun événement.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Titre</th><th>Date</th><th>Heure</th><th>Lieu</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($events as $ev): ?>
                <tr>
                    <td><?= e($ev['title']) ?></td>
                    <td><?= e($ev['event_date']) ?></td>
                    <td><?= e(substr((string)$ev['event_time'], 0, 5)) ?></td>
                    <td><?= e($ev['venue_name']) ?></td>
                    <td>
                        <div class="btn-row">
                            <a class="btn-nav-outline" href="event.php?id=<?= (int)$ev['id'] ?>">Détails</a>
                            <a class="btn-nav-outline" href="event_edit.php?id=<?= (int)$ev['id'] ?>">Modifier</a>
                            <form method="post" action="event_delete.php" class="inline-form js-confirm-delete"
                                  data-title="<?= e($ev['title']) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
                                <button type="submit" class="btn-danger-outline">Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php require __DIR__ . '/../includes/confirm_delete.php'; ?>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>