<?php $navUser = current_user(); ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Gestion des évènements') ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <nav class="nav">
        <a class="brand" href="index.php">Évènements</a>
        <span class="spacer"></span>
        <?php if ($navUser): ?>
            <span class="who"><?= e($navUser['name']) ?> (<?= e($navUser['role']) ?>)</span>
            <?php if ($navUser['role'] === 'admin'): ?><a href="admin_users.php">Utilisateurs</a><?php endif; ?>
            <a href="profile.php">Profil</a>
            <form method="post" action="logout.php" class="inline">
                <?= csrf_field() ?>
                <button type="submit">Déconnexion</button>
            </form>
        <?php else: ?>
            <a href="login.php">Connexion</a>
            <a href="register.php">Inscription</a>
        <?php endif; ?>
    </nav>
    <?php foreach (flash_get() as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>