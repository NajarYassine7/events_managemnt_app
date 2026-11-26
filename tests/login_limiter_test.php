<?php
require __DIR__ . '/../includes/login_limiter.php';

function check(string $label, bool $ok): void
{
    echo ($ok ? 'OK   ' : 'FAIL ') . $label . PHP_EOL;
}

$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
$email = 'limiter-test@ensa.test';
login_clear_failures($email);

for ($i = 1; $i <= 4; $i++) {
    login_record_failure($email);
}
check('4 failures: not blocked', login_blocked_seconds($email) === 0);

login_record_failure($email);
$wait = login_blocked_seconds($email);
check('5 failures: blocked', $wait > 0 && $wait <= LOGIN_WINDOW_MINUTES * 60);

$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
check('another IP is not blocked', login_blocked_seconds($email) === 0);

$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
login_clear_failures($email);
check('success clears the block', login_blocked_seconds($email) === 0);
