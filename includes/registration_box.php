<?php

/**
 * Registration box, required by public/event.php (B's page, guarded by is_file()).
 * Needs $event in scope: the row returned by Event::findById().
 * Variables are prefixed with $rb so they never clash with event.php's own.
 */
require_once __DIR__ . '/../models/Registration.php';

$rbUser = current_user();
if ($rbUser !== null && isset($event['id'])):
    $rbEventId = (int)$event['id'];
    $rbReg     = Registration::findByUserAndEvent((int)$rbUser['id'], $rbEventId);
    $rbStatus  = $rbReg['status'] ?? null;
    $rbPast    = Registration::eventIsPast($rbEventId);
    $rbTaken   = Registration::seatsTaken($rbEventId);
    $rbMax     = (int)$event['max_capacity'];
    $rbLeft    = max(0, $rbMax - $rbTaken);
?>
    <section class="card registration-box">
        <h2>Inscription</h2>
        <p><strong>Places :</strong> <?= $rbTaken ?> / <?= $rbMax ?> réservées (<?= $rbLeft ?> restante(s))</p>

        <?php if ($rbStatus !== null && $rbStatus !== 'cancelled'): ?>
            <p>
                Votre statut :
                <span class="badge badge-<?= e($rbStatus) ?>"><?= e(Registration::statusLabel($rbStatus)) ?></span>
            </p>

            <?php if ($rbStatus === 'rejected'): ?>
                <p>Votre inscription a été refusée par l'organisateur.</p>
            <?php elseif (Registration::canCancel($rbStatus, $rbPast)): ?>
                <form method="post" action="event_register.php"
                    onsubmit="return confirm('Annuler votre inscription ?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="registration_id" value="<?= (int)$rbReg['id'] ?>">
                    <input type="hidden" name="return" value="event">
                    <button type="submit" class="btn-danger-outline">Annuler mon inscription</button>
                </form>
            <?php endif; ?>

        <?php else: ?>
            <?php if ($rbStatus === 'cancelled'): ?>
                <p>Vous avez annulé votre inscription.</p>
            <?php endif; ?>

            <?php if ($rbPast): ?>
                <p>Cet événement est passé : les inscriptions sont fermées.</p>
            <?php elseif ($rbLeft === 0): ?>
                <p>Cet événement est complet.</p>
            <?php else: ?>
                <form method="post" action="event_register.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="event_id" value="<?= $rbEventId ?>">
                    <button type="submit" class="btn-nav-primary">S'inscrire</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </section>
<?php endif; ?>