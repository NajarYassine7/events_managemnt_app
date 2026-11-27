<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../models/Event.php';

require_login();                 // les événements sont réservés aux connectés
$events = Event::findAll();

$pageTitle = 'Événements';
require __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <h1>Événements</h1>

    <?php if (in_array(current_user()['role'], ['organizer', 'admin'], true)): ?>
        <p><a class="primary" href="event_create.php">Créer un événement</a></p>
    <?php endif; ?>

    <?php if ($events === []): ?>
        <p>Aucun événement pour le moment.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Titre</th><th>Date</th><th>Heure</th><th>Lieu</th><th>Places</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($events as $ev): ?>
                <tr>
                    <td><?= e($ev['title']) ?></td>
                    <td><?= e($ev['event_date']) ?></td>
                    <td><?= e(substr((string)$ev['event_time'], 0, 5)) ?></td>
                    <td><?= e($ev['venue_name']) ?></td>
                    <td><?= (int)$ev['max_capacity'] ?></td>
                    <td><a href="event.php?id=<?= (int)$ev['id'] ?>">Détails</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>