<?php

declare(strict_types=1);

function start_secure_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');   // refuse session ids the server did not create
    session_set_cookie_params([
        'lifetime' => 0,                       // until the browser closes
        'path'     => '/',
        'httponly' => true,                    // not readable from JavaScript
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

/**
 * The logged-in user (without password_hash), or null.
 * Loaded from the database on each request, so a role change or a deleted
 * account takes effect immediately.
 */
function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id)) {
        return $cache = null;
    }
    $user = User::findById($id);
    if ($user === null) {
        unset($_SESSION['user_id']);
    }
    return $cache = $user;
}

/** Call right after a successful password check. */
function login_user(array $user): void
{
    session_regenerate_id(true);               // prevents session fixation
    $_SESSION['user_id'] = (int)$user['id'];
    unset($_SESSION['csrf_token']);            // fresh token for the new session
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Anonymous visitors are sent to the login page. */
function require_login(): void
{
    if (current_user() === null) {
        flash_set('info', 'Veuillez vous connecter pour accéder à cette page.');
        redirect('login.php');
    }
}

/**
 * Requires one of the given roles, e.g. require_role(['organizer']).
 * The admin always passes (Admin inherits Organizer and Participant).
 * Anonymous: redirected to login. Wrong role: 403.
 */
function require_role(array $roles): void
{
    require_login();
    $role = current_user()['role'];
    if ($role !== 'admin' && !in_array($role, $roles, true)) {
        render_error(403, "Vous n'avez pas le droit d'accéder à cette page.");
    }
}
