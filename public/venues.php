<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../models/Venue.php';

require_role(['admin']);   // gestion des lieux : réservée à l'admin

$errors = [];
$old = ['name' => '', 'address' => '', 'capacity' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $action = (string)($_POST['action'] ?? 'create');

    if ($action === 'delete') {
        // --- Suppression d'un lieu ---
        $venueId = filter_var($_POST['venue_id'] ?? null, FILTER_VALIDATE_INT);
        $venue = $venueId === false ? null : Venue::findById($venueId);

        if ($venue === null) {
            flash_set('error', 'Lieu introuvable.');
        } else {
            try {
                Venue::delete((int)$venue['id']);
                flash_set('success', 'Lieu « ' . $venue['name'] . ' » supprimé.');
            } catch (PDOException $ex) {
                if (($ex->errorInfo[1] ?? 0) === 1451) {   // encore utilisé par des évènements (ON DELETE RESTRICT)
                    flash_set('error', 'Ce lieu est utilisé par des évènements : supprimez-les d\'abord.');
                } else {
                    throw $ex;
                }
            }
        }
        redirect('venues.php');
    }

    // --- Création d'un lieu ---
    $old = [
        'name'        => (string)($_POST['name'] ?? ''),
        'address'     => (string)($_POST['address'] ?? ''),
        'capacity'    => (string)($_POST['capacity'] ?? ''),
        'description' => (string)($_POST['description'] ?? ''),
    ];

    $errors = Venue::validate($old);
    if ($errors === []) {
        Venue::create($old);
        flash_set('success', 'Lieu « ' . trim($old['name']) . ' » ajouté.');
        redirect('venues.php');
    }
}

$venues = Venue::findAll();
$pageTitle = 'Gestion des lieux';
require __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <h1>Lieux</h1>

    <h2>Ajouter un lieu</h2>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">

        <label for="name">Nom</label>
        <input type="text" id="name" name="name" maxlength="150" required value="<?= e($old['name']) ?>">
        <?php if (isset($errors['name'])): ?><p class="error"><?= e($errors['name']) ?></p><?php endif; ?>

        <label for="address">Adresse</label>
        <input type="text" id="address" name="address" maxlength="255" required value="<?= e($old['address']) ?>">
        <?php if (isset($errors['address'])): ?><p class="error"><?= e($errors['address']) ?></p><?php endif; ?>

        <label for="capacity">Capacité</label>
        <input type="number" id="capacity" name="capacity" min="1" required value="<?= e($old['capacity']) ?>">
        <?php if (isset($errors['capacity'])): ?><p class="error"><?= e($errors['capacity']) ?></p><?php endif; ?>

        <label for="description">Description (facultatif)</label>
        <textarea id="description" name="description" rows="3"><?= e($old['description']) ?></textarea>
        <?php if (isset($errors['description'])): ?><p class="error"><?= e($errors['description']) ?></p><?php endif; ?>

        <button type="submit" class="primary">Ajouter</button>
    </form>

    <h2>Liste des lieux</h2>
    <?php if ($venues === []): ?>
        <p>Aucun lieu pour le moment.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Adresse</th>
                    <th>Capacité</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($venues as $v): ?>
                    <tr>
                        <td><?= e($v['name']) ?></td>
                        <td><?= e($v['address']) ?></td>
                        <td><?= (int)$v['capacity'] ?></td>
                        <td><?= e($v['description']) ?></td>
                        <td>
                            <form method="post" class="inline"
                                onsubmit="return confirm('Supprimer ce lieu ?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="venue_id" value="<?= (int)$v['id'] ?>">
                                <button type="submit" class="btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>