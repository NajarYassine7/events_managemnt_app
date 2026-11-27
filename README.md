# Application Web de Gestion des Évènements

Web app to plan and manage events (conferences, trainings, seminars, meetings): events and venues management, participant registration with validation and waiting list, attendance tracking, PDF attendance lists and statistics.

Academic project, ENSA El Jadida. Team of 4, Scrum method, 10-day schedule.

**Stack:** PHP 8 (PDO, no framework), MySQL (XAMPP), HTML/CSS, vanilla JS, Dompdf/FPDF, Chart.js.

## Setup (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) (PHP 8+).
2. Clone the repo **into `htdocs`**, keeping this folder name:
   ```
   cd C:\xampp\htdocs
   git clone https://github.com/NajarYassine7/events_managemnt_app.git events_management_app
   cd events_management_app
   git checkout develop
   ```
3. Start **Apache** and **MySQL** in the XAMPP Control Panel.
4. Open `http://localhost/phpmyadmin`, go to **Import**, and import in this order:
   1. `sql/schema.sql` (creates the `events_db` database and the tables)
   2. `sql/seed.sql` (test data)
5. Copy the config file (defaults work with XAMPP):
   ```
   copy config\database.example.php config\database.local.php
   ```
6. Open `http://localhost/events_management_app/public/`. It must print **Connected to the database.**

If it fails: MySQL not started, database not imported, or `config/database.local.php` missing.

## Test accounts

Password for **all** accounts: `Test1234!` (test data only, never use it anywhere real).

| Role | Email |
|---|---|
| Admin | `admin@ensa.test` |
| Organizer | `org1@ensa.test`, `org2@ensa.test` |
| Participant | `p1@ensa.test` to `p6@ensa.test` |

## Resetting your database

- **Reset the data only:** import `sql/seed.sql`.
- **After a schema change** (announced by the repo lead): import `sql/schema.sql`, then `sql/seed.sql`.

Each member has their own local database. Nothing is shared live: Git shares the structure and the test data.

## Project structure

| Folder | Purpose |
|---|---|
| `config/` | Database settings (`database.example.php` is committed, `database.local.php` is yours and git-ignored) |
| `models/` | Database access, one class per table (PDO prepared statements only) |
| `controllers/` | Request logic: validate, call models, choose the view |
| `views/` | HTML pages, no SQL |
| `includes/` | Shared helpers: `db.php`, auth guards, CSRF, `e()` escaping, header/footer |
| `public/` | Entry point and static files (`index.php`, `css/`, `js/`) |
| `sql/` | `schema.sql` (tables) and `seed.sql` (test data) |

## Team and sprints

| Member | Area |
|---|---|
| A | Users, authentication, roles, security, Trello |
| B | Events, report, diagrams |
| C | Venues, registrations (validation, waiting list), GitHub |
| D | Attendance, PDF lists, statistics, Gantt, video |

Sprint 0 (Day 1) design and setup · Sprint 1 (Days 2-3) auth · Sprint 2 (Days 4-5) events · Sprint 3 (Days 6-7) venues and registrations · Sprint 4 (Days 8-9) attendance, PDF, statistics · Sprint 5 (Day 10) integration, report, video.

## Git workflow

- Branches: `main` (stable, only merged from `develop` after a sprint demo), `develop` (integration), `feature/<module>-<task>` (your work, e.g. `feature/auth-login`).
- **Never push directly to `main` or `develop`.** Open a pull request into `develop`, with at least one review.
- Commit prefixes: `feat:`, `fix:`, `docs:`, `chore:`.
- Pull `develop` before starting work each day. Keep pull requests small and open them early.
- Never commit credentials: `config/database.local.php` is git-ignored.

## Shared conventions

- Business rules: a new registration is `pending`; seats taken = `pending` + `confirmed`; a full event puts new registrations on the waiting list (`waitlisted`, ordered by `registered_at`); cancelling or rejecting promotes the first waitlisted registration, inside a transaction.
- Naming: `venue` / `venue_id`, `max_capacity`, `event_date`, `event_time`, `password_hash`.
- Registration statuses: `pending`, `confirmed`, `waitlisted`, `cancelled`, `rejected`.
- Shared functions: `db()`, `current_user()`, `require_login()`, `require_role(array $roles)`, `csrf_token()`, `csrf_check()`, `e()`.
- Security baseline: prepared statements only, `password_hash`/`password_verify`, CSRF token on every POST form, escape all output with `e()`, validate on the server, check ownership on every edit/delete.