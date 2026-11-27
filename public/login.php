<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/login_limiter.php';

// Hash bcrypt factice pour éviter les attaques par analyse temporelle (timing attacks)
const DUMMY_HASH = '$2y$10$ZggdQHhvPR0Y/VD4/jpOSuVc9GvHIBaJL4.31gWiS9h1rxEI9K8.6';

if (current_user() !== null) {
    redirect('index.php');
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email    = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');

    $wait = login_blocked_seconds($email);
    if ($wait > 0) {
        $error = 'Trop de tentatives échouées. Réessayez dans ' . (int)ceil($wait / 60) . ' minute(s).';
    } else {
        $user = User::findByEmail($email);
        $hash = $user['password_hash'] ?? DUMMY_HASH;
        $ok   = password_verify($password, $hash) && $user !== null;

        if ($ok) {
            login_clear_failures($email);
            unset($user['password_hash']);
            login_user($user);
            flash_set('success', 'Ravi de vous revoir, ' . $user['name'] . ' !');
            redirect('index.php');
        }

        login_record_failure($email);
        $error = 'Adresse e-mail ou mot de passe incorrect.';
    }
}

$pageTitle = 'Connexion';
require __DIR__ . '/../includes/header.php';
?>
<main class="auth-wrapper">
    <div class="auth-header">
        <h1>Bon retour</h1>
        <p>Connectez-vous pour accéder à votre espace et gérer vos évènements.</p>
    </div>

    <form method="post" class="card" novalidate>
        <?= csrf_field() ?>

        <?php if ($error !== null): ?>
            <div class="flash flash-error" role="alert">
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="email">Adresse e-mail</label>
            <input
                id="email"
                name="email"
                type="email"
                placeholder="vous@exemple.com"
                value="<?= e($email) ?>"
                class="<?= $error !== null ? 'has-error' : '' ?>"
                required
                autofocus
                autocomplete="email"
            >
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <div class="input-wrapper">
                <input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="Votre mot de passe"
                    class="input-password <?= $error !== null ? 'has-error' : '' ?>"
                    required
                    autocomplete="current-password"
                >
                <button type="button" class="toggle-password" data-target="password" aria-label="Afficher ou masquer le mot de passe">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
        </div>

        <button class="primary" type="submit">Se connecter</button>
    </form>

    <p class="auth-footer">
        Pas encore de compte ? <a href="register.php">Créer un compte gratuitement</a>
    </p>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(btn.getAttribute('data-target'));
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>