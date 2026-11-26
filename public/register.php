<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if (current_user() !== null) {
    redirect('index.php');
}

$errors = [];
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name     = trim((string)($_POST['name'] ?? ''));
    $email    = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['password_confirm'] ?? '');
    $old = ['name' => $name, 'email' => $email];

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['name'] = 'Le nom doit contenir entre 2 et 100 caractères.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors['email'] = "L'adresse e-mail n'est pas valide.";
    }
    // 72 bytes is the bcrypt limit: longer passwords would be silently truncated.
    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors['password'] = 'Le mot de passe doit contenir entre 8 et 72 caractères.';
    }
    if ($password !== $confirm) {
        $errors['password_confirm'] = 'Les deux mots de passe ne correspondent pas.';
    }
    if (!isset($errors['email']) && User::emailExists($email)) {
        $errors['email'] = 'Cette adresse e-mail est déjà utilisée.';
    }

    if ($errors === []) {
        try {
            $id = User::create($name, $email, $password);    // role is always 'participant'
        } catch (PDOException $ex) {
            if (($ex->errorInfo[1] ?? 0) === 1062) {         // duplicate email (two sign-ups at once)
                $errors['email'] = 'Cette adresse e-mail est déjà utilisée.';
            } else {
                throw $ex;
            }
        }
        if ($errors === []) {
            login_user(User::findById($id));
            flash_set('success', 'Compte créé. Bienvenue, ' . $name . ' !');
            redirect('index.php');
        }
    }
}

$pageTitle = 'Inscription';
require __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <h1>Créer un compte</h1>
    <form method="post" class="card" novalidate>
        <?= csrf_field() ?>

        <label for="name">Nom complet</label>
        <input id="name" name="name" value="<?= e($old['name']) ?>" required maxlength="100">
        <?php if (isset($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>

        <label for="email">Adresse e-mail</label>
        <input id="email" name="email" type="email" value="<?= e($old['email']) ?>" required maxlength="150">
        <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>

        <label for="password">Mot de passe (8 caractères minimum)</label>
        <input id="password" name="password" type="password" required autocomplete="new-password">
        <?php if (isset($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>

        <label for="password_confirm">Confirmer le mot de passe</label>
        <input id="password_confirm" name="password_confirm" type="password" required autocomplete="new-password">
        <?php if (isset($errors['password_confirm'])): ?><div class="field-error"><?= e($errors['password_confirm']) ?></div><?php endif; ?>

        <button class="primary" type="submit">Créer mon compte</button>
    </form>
    <p>Déjà inscrit ? <a href="login.php">Se connecter</a></p>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>