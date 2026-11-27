<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

require_role(['admin']);            // anonymous: redirected to login; any other role: 403
$me = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $action   = (string)($_POST['action'] ?? '');
    $targetId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
    $target   = $targetId === false ? null : User::findById($targetId);

    if ($target === null) {
        flash_set('error', 'Utilisateur introuvable.');
    } elseif ((int)$target['id'] === (int)$me['id']) {
        flash_set('error', 'Vous ne pouvez pas modifier ou supprimer votre propre compte.');
    } elseif ($action === 'role') {
        $role = (string)($_POST['role'] ?? '');
        if (!in_array($role, ['admin', 'organizer', 'participant'], true)) {
            flash_set('error', 'Rôle invalide.');
        } else {
            User::setRole((int)$target['id'], $role);
            flash_set('success', 'Rôle de ' . $target['name'] . ' : ' . $role . '.');
        }
    } elseif ($action === 'delete') {
        try {
            User::delete((int)$target['id']);
            flash_set('success', 'Compte de ' . $target['name'] . ' supprimé.');
        } catch (PDOException $ex) {
            if (($ex->errorInfo[1] ?? 0) === 1451) {   // still referenced by events (ON DELETE RESTRICT)
                flash_set('error', $target['name'] . ' organise encore des évènements : supprimez-les d\'abord.');
            } else {
                throw $ex;
            }
        }
    } else {
        flash_set('error', 'Action inconnue.');
    }
    redirect('admin_users.php');
}

$users = User::findAll();
$pageTitle = 'Gestion des utilisateurs';
require __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <h1>Utilisateurs</h1>
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>E-mail</th>
                <th>Rôle</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['role']) ?></td>
                    <td>
                        <?php if ((int)$u['id'] === (int)$me['id']): ?>
                            <em>(vous)</em>
                        <?php else: ?>
                            <form method="post" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="role">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <select name="role" aria-label="Rôle de <?= e($u['name']) ?>">
                                    <?php foreach (['participant', 'organizer', 'admin'] as $r): ?>
                                        <option value="<?= $r ?>" <?= $u['role'] === $r ? ' selected' : '' ?>><?= $r ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit">Changer</button>
                            </form>
                            <form method="post" class="inline"
                                onsubmit="return confirm('Supprimer ce compte et ses inscriptions ?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <button type="submit">Supprimer</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>