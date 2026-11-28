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
if (!Event::canManage($event, current_user())) {
    render_error(403, "Vous n'avez pas le droit de modifier cet événement.");
}

$venues = Venue::findAll();
$errors = [];
$old = [
    'title'        => (string)$event['title'],
    'description'  => (string)($event['description'] ?? ''),
    'event_date'   => (string)$event['event_date'],
    'event_time'   => substr((string)$event['event_time'], 0, 5),
    'venue_id'     => (string)$event['venue_id'],
    'max_capacity' => (string)$event['max_capacity'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($old as $key => $_) {
        $old[$key] = trim((string)($_POST[$key] ?? ''));
    }

    $errors = Event::validate($old, (int)$event['id']);
    if ($errors === []) {
        Event::update((int)$event['id'], $old);
        flash_set('success', 'Événement mis à jour avec succès.');
        redirect('event.php?id=' . (int)$event['id']);
    }
}

$pageTitle = 'Modifier : ' . $event['title'];
require __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <h1>Modifier l'événement</h1>

    <form method="post" class="card" novalidate>
        <?= csrf_field() ?>

        <label for="title">Titre</label>
        <input id="title" name="title" value="<?= e($old['title']) ?>" required maxlength="150">
        <?php if (isset($errors['title'])): ?><div class="field-error"><?= e($errors['title']) ?></div><?php endif; ?>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"><?= e($old['description']) ?></textarea>
        <?php if (isset($errors['description'])): ?><div class="field-error"><?= e($errors['description']) ?></div><?php endif; ?>

        <label for="event_date">Date</label>
        <input id="event_date" name="event_date" type="date" value="<?= e($old['event_date']) ?>" required>
        <?php if (isset($errors['event_date'])): ?><div class="field-error"><?= e($errors['event_date']) ?></div><?php endif; ?>

        <label for="event_time">Heure</label>
        <input id="event_time" name="event_time" type="time" value="<?= e($old['event_time']) ?>" required>
        <?php if (isset($errors['event_time'])): ?><div class="field-error"><?= e($errors['event_time']) ?></div><?php endif; ?>

        <label for="venue_id">Lieu</label>
        <select id="venue_id" name="venue_id" required>
            <option value="">-- Choisir un lieu --</option>
            <?php foreach ($venues as $v): ?>
                <option value="<?= (int)$v['id'] ?>" <?= (string)$v['id'] === $old['venue_id'] ? 'selected' : '' ?>>
                    <?= e($v['name']) ?> (<?= (int)$v['capacity'] ?> places)
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['venue_id'])): ?><div class="field-error"><?= e($errors['venue_id']) ?></div><?php endif; ?>

        <label for="max_capacity">Capacité maximale</label>
        <input id="max_capacity" name="max_capacity" type="number" min="1" value="<?= e($old['max_capacity']) ?>" required>
        <?php if (isset($errors['max_capacity'])): ?><div class="field-error"><?= e($errors['max_capacity']) ?></div><?php endif; ?>

        <div class="btn-row">
            <button class="btn-nav-primary" type="submit">Enregistrer</button>
            <a class="btn-nav-outline" href="event.php?id=<?= (int)$event['id'] ?>">Annuler</a>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
