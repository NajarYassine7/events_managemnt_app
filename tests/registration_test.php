<?php

declare(strict_types=1);

/**
 * Run with:  /mnt/c/xampp/php/php.exe tests/registration_test.php
 * Needs a freshly imported seed.sql (schema.sql, then seed.sql).
 * Assumes the seed dates: events 1-3 in the future (event 2 is on 2026-12-05), event 4 in the past.
 * The script restores what it changes; re-import seed.sql afterwards anyway.
 */
require __DIR__ . '/../models/Registration.php';

function check(string $label, bool $ok): void
{
    echo ($ok ? 'OK   ' : 'FAIL ') . $label . PHP_EOL;
}

// ---- seat counting -------------------------------------------------------
check('seatsTaken event 1 = 3 (2 confirmed + 1 pending)', Registration::seatsTaken(1) === 3);
check('seatsTaken event 2 = 3 (full)',                    Registration::seatsTaken(2) === 3);
check('seatsTaken event 3 = 1',                           Registration::seatsTaken(3) === 1);
check('countConfirmed event 1 = 2',                       Registration::countConfirmed(1) === 2);
check('countConfirmed event 2 = 2',                       Registration::countConfirmed(2) === 2);
check('countConfirmed event 3 = 0',                       Registration::countConfirmed(3) === 0);

// ---- finders -------------------------------------------------------------
$r = Registration::findByUserAndEvent(4, 2);
check('findByUserAndEvent(4, 2) is confirmed',            $r !== null && $r['status'] === 'confirmed');
check('findByUserAndEvent returns null when none',        Registration::findByUserAndEvent(7, 1) === null);

$list = Registration::findByUser(4);
check('findByUser(4) returns 4 rows',                     count($list) === 4);
check('findByUser lists the newest event first',          count($list) > 0 && (int)$list[0]['event_id'] === 3);
check('past event is flagged is_past',                    count($list) === 4 && (int)$list[3]['is_past'] === 1);

// ---- refusals ------------------------------------------------------------
$res = Registration::register(4, 1);
check('already registered is refused (409)',              !$res['ok'] && $res['code'] === 409);

$res = Registration::register(5, 3);
check('rejected user cannot register again (409)',        !$res['ok'] && $res['code'] === 409);

$res = Registration::register(9, 2);
check('full event refuses the registration',              !$res['ok'] && str_contains($res['message'], 'complet'));

$res = Registration::register(7, 4);
check('past event refuses the registration',              !$res['ok'] && str_contains($res['message'], 'passé'));

$res = Registration::register(7, 999);
check('unknown event gives 404',                          !$res['ok'] && $res['code'] === 404);

// ---- a new registration (user 7 on event 1), then clean up ----------------
$res = Registration::register(7, 1);
$new = Registration::findByUserAndEvent(7, 1);
check('new registration is created as pending',           $res['ok'] && $new !== null && $new['status'] === 'pending');
check('seatsTaken event 1 is now 4',                      Registration::seatsTaken(1) === 4);
if ($new !== null) {
    db()->prepare('DELETE FROM registrations WHERE id = ?')->execute([(int)$new['id']]);
}

// ---- re-registering after a cancellation reuses the row (user 9, event 3, row 11)
$res = Registration::register(9, 3);
$row = Registration::findByUserAndEvent(9, 3);
check(
    'cancelled user can register again, same row reused',
    $res['ok'] && $row !== null && (int)$row['id'] === 11 && $row['status'] === 'pending'
);
db()->exec("UPDATE registrations SET status = 'cancelled', registered_at = '2026-11-21 11:20:00' WHERE id = 11");

// ---- cancellation --------------------------------------------------------
$res = Registration::cancel(4, 14);     // registration 14 belongs to user 6
check("cannot cancel another user's registration (403)",  !$res['ok'] && $res['code'] === 403);

$res = Registration::cancel(4, 999);
check('unknown registration gives 404',                   !$res['ok'] && $res['code'] === 404);

$res = Registration::cancel(9, 11);     // already cancelled
check('already cancelled cannot be cancelled again',      !$res['ok'] && $res['code'] === 409);

$res = Registration::cancel(5, 10);     // rejected
check('rejected cannot be cancelled',                     !$res['ok'] && $res['code'] === 409);

$res = Registration::cancel(4, 12);     // confirmed, but event 4 is past
check('cancellation refused for a past event',            !$res['ok'] && $res['code'] === 409);

$res = Registration::cancel(4, 9);      // pending, event 3
$after = Registration::findById(9);
check('owner can cancel a pending registration',          $res['ok'] && $after !== null && $after['status'] === 'cancelled');
check('cancelling frees the seat (event 3: 1 -> 0)',      Registration::seatsTaken(3) === 0);
db()->exec("UPDATE registrations SET status = 'pending' WHERE id = 9");   // restore the seed

echo PHP_EOL . 'Done. Re-import sql/seed.sql to be sure the data is back to its original state.' . PHP_EOL;
