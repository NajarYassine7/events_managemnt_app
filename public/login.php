<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/login_limiter.php';

// A valid bcrypt hash, only used so that an unknown email takes as long to
// check as a known one (otherwise response time reveals which emails exist).
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

    $wait = login_blocked_seconds($email);                 // 1. blocked? check BEFORE the password
    if ($wait > 0) {
        $error = 'Trop de tentatives échouées. Réessayez dans ' . (int)ceil($wait / 60) . ' minute(s).';
    } else {
        $user = User::findByEmail($email);
        $hash = $user['password_hash'] ?? DUMMY_HASH;
        $ok   = password_verify($password, $hash) && $user !== null;

        if ($ok) {
            login_clear_failures($email);                  // 2. success: reset the counter
            unset($user['password_hash']);
            login_user($user);                             //    session_regenerate_id inside
            flash_set('success', 'Bienvenue, ' . $user['name'] . ' !');
            redirect('index.php');
        }

        login_record_failure($email);                      // 3. failure: record it
        $error = 'E-mail ou mot de passe incorrect.';      //    same message whatever the cause
    }
}

$pageTitle = 'Connexion';
require __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <h1>Connexion</h1>
    <form method="post" class="card">
        <?= csrf_field() ?>
        <?php if ($error !== null): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

        <label for="email">Adresse e-mail</label>
        <input id="email" name="email" type="email" value="<?= e($email) ?>" required autofocus>

        <label for="password">Mot de passe</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">

        <button class="primary" type="submit">Se connecter</button>
    </form>
    <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>