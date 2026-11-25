<?php
require __DIR__ . '/../models/Venue.php';

function check(string $label, bool $ok): void
{
    echo ($ok ? 'OK   ' : 'FAIL ') . $label . PHP_EOL;
}

// validation
check('valid data has no errors', Venue::validate([
    'name' => 'Salle test',
    'address' => 'Bâtiment D',
    'capacity' => '40',
]) === []);
check('empty name rejected',      isset(Venue::validate(['name' => '', 'address' => 'x', 'capacity' => 10])['name']));
check('capacity 0 rejected',      isset(Venue::validate(['name' => 'a', 'address' => 'x', 'capacity' => 0])['capacity']));
check('capacity "abc" rejected',  isset(Venue::validate(['name' => 'a', 'address' => 'x', 'capacity' => 'abc'])['capacity']));

// create / find / list
$id = Venue::create(['name' => 'Salle test', 'address' => 'Bâtiment D', 'capacity' => '40', 'description' => '']);
$venue = Venue::findById($id);
check('created venue can be found',   $venue !== null && $venue['name'] === 'Salle test');
check('empty description stored as NULL', $venue !== null && $venue['description'] === null);
check('unknown id returns null',      Venue::findById(999999) === null);
check('findAll contains the new venue', in_array($id, array_column(Venue::findAll(), 'id'), true));

// cleanup so the seed data stays untouched
db()->prepare('DELETE FROM venues WHERE id = ?')->execute([$id]);
check('test venue cleaned up',        Venue::findById($id) === null);
