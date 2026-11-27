<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');          // a link or a GET never logs anyone out
}
csrf_check();

logout_user();                      // destroys the session and the cookie
start_secure_session();             // fresh session, only to carry the message
flash_set('info', 'Vous êtes déconnecté.');
redirect('login.php');
