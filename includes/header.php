<?php
$navUser = current_user();
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');

$roleLabels = [
    'admin'       => 'Admin',
    'organizer'   => 'Organisateur',
    'participant' => 'Participant',
];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Gestion des évènements') ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <header class="navbar">
        <div class="nav-container">
            <a class="brand" href="index.php">
                <span class="brand-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </span>
                <span>EventHub</span>
            </a>

            <button class="nav-toggle" type="button" id="navToggle" aria-label="Ouvrir le menu" aria-expanded="false">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <div class="nav-collapse" id="navCollapse" style="display: flex; flex: 1; align-items: center; justify-content: space-between;">
                <nav class="nav-menu" aria-label="Navigation principale">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">Accueil</a>
                    <a class="nav-link <?= in_array($currentPage, ['events.php', 'event.php'], true) ? 'active' : '' ?>" href="events.php">Évènements</a>

                    <?php if ($navUser && in_array($navUser['role'], ['organizer', 'admin'], true)): ?>
                        <a class="nav-link <?= $currentPage === 'event_create.php' ? 'active' : '' ?>" href="event_create.php">+ Créer un évènement</a>
                    <?php endif; ?>

                    <?php if ($navUser && $navUser['role'] === 'admin'): ?>
                        <a class="nav-link <?= $currentPage === 'admin_users.php' ? 'active' : '' ?>" href="admin_users.php">Gestion utilisateurs</a>
                    <?php endif; ?>
                </nav>

                <div class="nav-actions">
                    <?php if ($navUser): ?>
                        <?php
                        $role = $navUser['role'] ?? 'participant';
                        $roleLabel = $roleLabels[$role] ?? $role;
                        ?>
                        <span class="user-pill">
                            <span><?= e($navUser['name']) ?></span>
                            <span class="role-badge role-<?= e($role) ?>"><?= e($roleLabel) ?></span>
                        </span>
                        <a class="btn-nav-outline <?= $currentPage === 'profile.php' ? 'active' : '' ?>" href="profile.php">Mon profil</a>
                        <form method="post" action="logout.php" class="inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn-logout">Déconnexion</button>
                        </form>
                    <?php else: ?>
                        <a class="btn-nav-outline" href="login.php">Connexion</a>
                        <a class="btn-nav-primary" href="register.php">Inscription</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="main-content">
        <?php $flashes = flash_get(); ?>
        <?php if (!empty($flashes)): ?>
            <div class="flash-container">
                <?php foreach ($flashes as $f): ?>
                    <div class="flash flash-<?= e($f['type']) ?>" role="alert">
                        <span><?= e($f['msg']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>