<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Accueil';
require __DIR__ . '/../includes/header.php';
$user = current_user();
?>
<main class="container">
    <h1>Gestion des évènements</h1>
    <?php if ($user): ?>
        <p>Bonjour <?= e($user['name']) ?>, vous êtes connecté en tant que <strong><?= e($user['role']) ?></strong>.</p>
    <?php else: ?>
        <p>Consultez les évènements, inscrivez-vous et suivez vos participations.</p>
        <p><a href="login.php">Connexion</a> · <a href="register.php">Inscription</a></p>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>