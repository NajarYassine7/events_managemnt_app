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
            flash_set('success', 'Compte créé avec succès. Bienvenue, ' . $name . ' !');
            redirect('index.php');
        }
    }
}

$pageTitle = 'Inscription';
require __DIR__ . '/../includes/header.php';
?>
<main class="auth-wrapper">
    <div class="auth-header">
        <h1>Créer un compte</h1>
        <p>Rejoignez la plateforme pour découvrir et réserver vos évènements.</p>
    </div>

    <form method="post" class="card" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="name">Nom complet</label>
            <input
                id="name"
                name="name"
                type="text"
                placeholder="Ex. Noussaiba Zirar"
                value="<?= e($old['name']) ?>"
                class="<?= isset($errors['name']) ? 'has-error' : '' ?>"
                required
                maxlength="100"
                autofocus
            >
            <?php if (isset($errors['name'])): ?>
                <div class="field-error"><?= e($errors['name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">Adresse e-mail</label>
            <input
                id="email"
                name="email"
                type="email"
                placeholder="vous@exemple.com"
                value="<?= e($old['email']) ?>"
                class="<?= isset($errors['email']) ? 'has-error' : '' ?>"
                required
                maxlength="150"
            >
            <?php if (isset($errors['email'])): ?>
                <div class="field-error"><?= e($errors['email']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <div class="input-wrapper">
                <input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="8 caractères minimum"
                    class="input-password <?= isset($errors['password']) ? 'has-error' : '' ?>"
                    required
                    autocomplete="new-password"
                >
                <button type="button" class="toggle-password" data-target="password" aria-label="Afficher ou masquer le mot de passe">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
            <div class="strength-meter" id="strengthMeter" aria-live="polite">
                <div class="strength-bars">
                    <div class="strength-bar" id="bar-1"></div>
                    <div class="strength-bar" id="bar-2"></div>
                    <div class="strength-bar" id="bar-3"></div>
                    <div class="strength-bar" id="bar-4"></div>
                </div>
                <span class="strength-text" id="strengthText">Minimum 8 caractères (majuscules, chiffres et symboles conseillés)</span>
            </div>
            <?php if (isset($errors['password'])): ?>
                <div class="field-error"><?= e($errors['password']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password_confirm">Confirmer le mot de passe</label>
            <div class="input-wrapper">
                <input
                    id="password_confirm"
                    name="password_confirm"
                    type="password"
                    placeholder="Répétez votre mot de passe"
                    class="input-password <?= isset($errors['password_confirm']) ? 'has-error' : '' ?>"
                    required
                    autocomplete="new-password"
                >
                <button type="button" class="toggle-password" data-target="password_confirm" aria-label="Afficher ou masquer la confirmation">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
            <?php if (isset($errors['password_confirm'])): ?>
                <div class="field-error"><?= e($errors['password_confirm']) ?></div>
            <?php endif; ?>
        </div>

        <button class="primary" type="submit">Créer mon compte</button>
    </form>

    <p class="auth-footer">
        Déjà inscrit ? <a href="login.php">Se connecter</a>
    </p>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Afficher / masquer les mots de passe
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(btn.getAttribute('data-target'));
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });

    // Jauge de force du mot de passe
    const pwdInput = document.getElementById('password');
    const strengthText = document.getElementById('strengthText');
    const bars = [
        document.getElementById('bar-1'),
        document.getElementById('bar-2'),
        document.getElementById('bar-3'),
        document.getElementById('bar-4')
    ];

    if (pwdInput && strengthText) {
        pwdInput.addEventListener('input', function () {
            const val = pwdInput.value;
            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const levels = [
                { label: 'Minimum 8 caractères requis', color: '#e2e8f0' },
                { label: 'Force : Faible', color: '#ef4444' },
                { label: 'Force : Moyenne', color: '#f59e0b' },
                { label: 'Force : Bonne', color: '#3b82f6' },
                { label: 'Force : Excellente', color: '#10b981' }
            ];

            const activeLevel = val.length === 0 ? 0 : Math.max(1, score);
            bars.forEach(function (bar, index) {
                bar.style.backgroundColor = (val.length > 0 && index < activeLevel)
                    ? levels[activeLevel].color
                    : '#e2e8f0';
            });
            strengthText.textContent = levels[activeLevel].label;
            strengthText.style.color = val.length === 0 ? '#64748b' : levels[activeLevel].color;
        });
    }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>