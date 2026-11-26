<?php

declare(strict_types=1);

/** Escapes a value for safe HTML output. Use on EVERY value printed in a view. */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirects and stops the script. Paths are relative to public/ (flat pages). */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** One-time messages shown at the top of the next page. $type: success | error | info */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function flash_get(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Renders an error page with the layout and stops. */
function render_error(int $code, string $message): void
{
    http_response_code($code);
    $pageTitle = 'Erreur ' . $code;
    require __DIR__ . '/header.php';
    echo '<main class="container"><h1>Erreur ' . $code . '</h1><p>' . e($message) . '</p></main>';
    require __DIR__ . '/footer.php';
    exit;
}
