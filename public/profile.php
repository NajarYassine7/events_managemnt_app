<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

require_login();
$user = current_user();

$errors = [];
$pwErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form = (string)($_POST['form'] ?? '');

    if ($form === 'info') {
        $name  = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone = trim((string)($_POST['phone'] ?? ''));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = 'Le nom doit contenir entre 2 et 100 caractères.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $errors['email'] = "L'adresse e-mail n'est pas valide.";
        } elseif (User::emailTakenByOther($email, (int)$user['id'])) {
            $errors['email'] = 'Cette adresse e-mail est déjà utilisée.';
        }
        if ($phone !== '' && !preg_match('/^\+?[0-9 .\-]{6,20}$/', $phone)) {
            $errors['phone'] = 'Numéro de téléphone invalide (chiffres, espaces, + . - uniquement).';
        }

        if ($errors === []) {
            User::updateProfile((int)$user['id'], $name, $email, $phone === '' ? null : $phone);
            flash_set('success', 'Profil mis à jour.');
            redirect('profile.php');
        }
        // keep what the user typed
        $user['name'] = $name;
        $user['email'] = $email;
        $user['phone'] = $phone;
    } elseif ($form === 'password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new     = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['new_password_confirm'] ?? '');

        $hash = User::passwordHashOf((int)$user['id']);
        if ($hash === null || !password_verify($current, $hash)) {
            $pwErrors['current_password'] = 'Mot de passe actuel incorrect.';
        }
        if (strlen($new) < 8 || strlen($new) > 72) {
            $pwErrors['new_password'] = 'Le nouveau mot de passe doit contenir entre 8 et 72 caractères.';
        }
        if ($new !== $confirm) {
            $pwErrors['new_password_confirm'] = 'Les deux mots de passe ne correspondent pas.';
        }

        if ($pwErrors === []) {
            User::setPassword((int)$user['id'], $new);
            session_regenerate_id(true);               // new session id after a credential change
            flash_set('success', 'Mot de passe modifié.');
            redirect('profile.php');
        }
    }
}

$pageTitle = 'Mon profil';
require __DIR__ . '/../includes/header.php';
?>
<main class="container">
    <h1>Mon profil</h1>
    <p>Rôle : <strong><?= e($user['role']) ?></strong> (modifiable uniquement par un administrateur)</p>

    <form method="post" class="card" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="info">

        <label for="name">Nom complet</label>
        <input id="name" name="name" value="<?= e($user['name']) ?>" required maxlength="100">
        <?php if (isset($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>

        <label for="email">Adresse e-mail</label>
        <input id="email" name="email" type="email" value="<?= e($user['email']) ?>" required maxlength="150">
        <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>

        <label for="phone">Téléphone (facultatif)</label>
        <input id="phone" name="phone" value="<?= e($user['phone'] ?? '') ?>" maxlength="20">
        <?php if (isset($errors['phone'])): ?><div class="field-error"><?= e($errors['phone']) ?></div><?php endif; ?>

        <button class="primary" type="submit">Enregistrer</button>
    </form>

    <h2>Changer le mot de passe</h2>
    <form method="post" class="card" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="password">

        <label for="current_password">Mot de passe actuel</label>
        <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
        <?php if (isset($pwErrors['current_password'])): ?><div class="field-error"><?= e($pwErrors['current_password']) ?></div><?php endif; ?>

        <label for="new_password">Nouveau mot de passe (8 caractères minimum)</label>
        <input id="new_password" name="new_password" type="password" required autocomplete="new-password">
        <?php if (isset($pwErrors['new_password'])): ?><div class="field-error"><?= e($pwErrors['new_password']) ?></div><?php endif; ?>

        <label for="new_password_confirm">Confirmer le nouveau mot de passe</label>
        <input id="new_password_confirm" name="new_password_confirm" type="password" required autocomplete="new-password">
        <?php if (isset($pwErrors['new_password_confirm'])): ?><div class="field-error"><?= e($pwErrors['new_password_confirm']) ?></div><?php endif; ?>

        <button class="primary" type="submit">Modifier le mot de passe</button>
    </form>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>