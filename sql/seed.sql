-- Event management app: test data
-- Run AFTER schema.sql. Re-runnable: it empties the tables first.
-- Every account has the same password: Test1234!

USE events_db;

-- Empty the tables, children first (TRUNCATE fails on tables referenced by foreign keys)
DELETE FROM attendance;
DELETE FROM registrations;
DELETE FROM events;
DELETE FROM venues;
DELETE FROM users;
DELETE FROM login_attempts;

-- Restart the id counters so a reset always gives the same ids
ALTER TABLE attendance    AUTO_INCREMENT = 1;
ALTER TABLE registrations AUTO_INCREMENT = 1;
ALTER TABLE events        AUTO_INCREMENT = 1;
ALTER TABLE venues        AUTO_INCREMENT = 1;
ALTER TABLE users         AUTO_INCREMENT = 1;

-- ---------------------------------------------------------------
-- users (password for all: Test1234!)
-- ---------------------------------------------------------------
INSERT INTO users (id, name, email, password_hash, phone, role) VALUES
(1, 'Admin Demo',        'admin@ensa.test', '$2y$10$ZggdQHhvPR0Y/VD4/jpOSuVc9GvHIBaJL4.31gWiS9h1rxEI9K8.6', NULL,           'admin'),
(2, 'Organisateur Un',   'org1@ensa.test',  '$2y$10$ZggdQHhvPR0Y/VD4/jpOSuVc9GvHIBaJL4.31gWiS9h1rxEI9K8.6', '0600000001',   'organizer'),
(3, 'Organisateur Deux', 'org2@ensa.test',  '$2y$10$ZggdQHhvPR0Y/VD4/jpOSuVc9GvHIBaJL4.31gWiS9h1rxEI9K8.6', NULL,           'organizer'),
(4, 'Participant Un',    'p1@ensa.test',    '$2y$10$ZggdQHhvPR0Y/VD4/jpOSuVc9GvHIBaJL4.31gWiS9h1rxEI9K8.6', '0600000004',   'participant'),
(5, 'Participant Deux',  'p2@ensa.test',    '$2y$10$ZggdQHhvPR0Y/VD4/jpOSuVc9GvHIBaJL4.31gWiS9h1rxEI9K8.6', NULL,           'participant'),
(6, 'Participant Trois', 'p3@ensa.test',    '$2y$10$ZggdQHhvPR0Y/VD4/jpOSuVc9GvHIBaJL4.31gWiS9h1rxEI9K8.6', NULL,           'participant'),
(7, 'Participant Quatre','p4@ensa.test',    '$2y$10$ZggdQHhvPR0Y/VD4/jpOSuVc9GvHIBaJL4.31gWiS9h1rxEI9K8.6', NULL,           'participant'),
(8, 'Participant Cinq',  'p5@ensa.test',    '$2y$10$ZggdQHhvPR0Y/VD4/jpOSuVc9GvHIBaJL4.31gWiS9h1rxEI9K8.6', NULL,           'participant'),
(9, 'Participant Six',   'p6@ensa.test',    '$2y$10$ZggdQHhvPR0Y/VD4/jpOSuVc9GvHIBaJL4.31gWiS9h1rxEI9K8.6', NULL,           'participant');

-- ---------------------------------------------------------------
-- venues (different capacities)
-- ---------------------------------------------------------------
INSERT INTO venues (id, name, address, capacity, description) VALUES
(1, 'Amphithéâtre A',        'Bâtiment principal, rez-de-chaussée', 200, 'Grand amphithéâtre avec vidéoprojecteur'),
(2, 'Salle de séminaire',    'Bâtiment B, 1er étage',                20, 'Salle de réunion, tables en U'),
(3, 'Salle informatique 2',  'Bâtiment C, 2e étage',                 25, 'Postes de travail pour ateliers pratiques');

-- ---------------------------------------------------------------
-- events (max_capacity <= venue capacity)
-- 1: upcoming, large | 2: FULL with a waiting list | 3: upcoming, mixed statuses
-- 4: PAST event, used for attendance and statistics
-- ---------------------------------------------------------------
INSERT INTO events (id, title, description, event_date, event_time, venue_id, max_capacity, organizer_id) VALUES
(1, 'Conférence sur la cybersécurité', 'Introduction aux menaces actuelles et aux bonnes pratiques.',     '2026-12-10', '10:00:00', 1, 150, 2),
(2, 'Atelier PHP et MySQL',            'Atelier pratique, places limitées.',                              '2026-12-05', '14:00:00', 3,   3, 2),
(3, 'Séminaire Erasmus',               'Présentation des bourses et du processus de candidature.',        '2026-12-15', '09:30:00', 2,  20, 3),
(4, 'Journée portes ouvertes',         'Événement passé, utilisé pour les présences et les statistiques.', '2026-11-10', '09:00:00', 1, 100, 2);

-- ---------------------------------------------------------------
-- registrations (registered_at is explicit so the waiting list order is stable)
-- Event 2: max 3 -> seats taken = 3 (2 confirmed + 1 pending), 2 waitlisted
-- ---------------------------------------------------------------
INSERT INTO registrations (id, user_id, event_id, status, registered_at) VALUES
-- event 1: conference
(1,  4, 1, 'confirmed',  '2026-11-18 10:00:00'),
(2,  5, 1, 'confirmed',  '2026-11-18 10:05:00'),
(3,  9, 1, 'pending',    '2026-11-19 08:30:00'),
-- event 2: FULL + waiting list
(4,  4, 2, 'confirmed',  '2026-11-20 09:00:00'),
(5,  5, 2, 'confirmed',  '2026-11-20 09:05:00'),
(6,  6, 2, 'pending',    '2026-11-20 09:10:00'),
(7,  7, 2, 'waitlisted', '2026-11-20 09:20:00'),
(8,  8, 2, 'waitlisted', '2026-11-20 09:30:00'),
-- event 3: all the other statuses
(9,  4, 3, 'pending',    '2026-11-21 11:00:00'),
(10, 5, 3, 'rejected',   '2026-11-21 11:10:00'),
(11, 9, 3, 'cancelled',  '2026-11-21 11:20:00'),
-- event 4: past event
(12, 4, 4, 'confirmed',  '2026-11-01 09:00:00'),
(13, 5, 4, 'confirmed',  '2026-11-01 09:10:00'),
(14, 6, 4, 'confirmed',  '2026-11-01 09:20:00');

-- ---------------------------------------------------------------
-- attendance (only for the past event, marked by its organizer, user 2)
-- ---------------------------------------------------------------
INSERT INTO attendance (registration_id, present, marked_at, marked_by) VALUES
(12, 1, '2026-11-10 09:15:00', 2),
(13, 1, '2026-11-10 09:16:00', 2),
(14, 0, '2026-11-10 09:17:00', 2);