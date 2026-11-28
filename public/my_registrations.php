<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../models/Registration.php';

require_login();
$user = current_user();

$registrations = Registration::findByUser((int)$user['id']);

/** 2026-12-10 -> 10/12/2026 */
function reg_format_date(string $date): string
{
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    return $d === false ? $date : $d->format('d/m/Y');
}

$pageTitle = 'Mes inscriptions';
require __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <h1>Mes inscriptions</h1>

    <?php if ($registrations === []): ?>
        <div class="card">
            <p>Vous n'avez aucune inscription pour le moment.</p>
            <p><a href="events.php">Voir les événements</a></p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Événement</th>
                    <th>Date</th>
                    <th>Lieu</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registrations as $r): ?>
                    <?php $isPast = (int)$r['is_past'] === 1; ?>
                    <tr>
                        <td><a href="event.php?id=<?= (int)$r['event_id'] ?>"><?= e($r['title']) ?></a></td>
                        <td>
                            <?= e(reg_format_date((string)$r['event_date'])) ?>
                            <?= e(substr((string)$r['event_time'], 0, 5)) ?>
                            <?php if ($isPast): ?><em>(passé)</em><?php endif; ?>
                        </td>
                        <td><?= e($r['venue_name']) ?></td>
                        <td>
                            <span class="badge badge-<?= e($r['status']) ?>">
                                <?= e(Registration::statusLabel($r['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (Registration::canCancel($r['status'], $isPast)): ?>
                                <form method="post" action="event_register.php" class="inline"
                                    onsubmit="return confirm('Annuler cette inscription ?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="registration_id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="return" value="list">
                                    <button type="submit">Annuler</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>