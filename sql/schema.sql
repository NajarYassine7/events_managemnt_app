-- Event management app: database schema
-- Re-runnable: drops and recreates all tables (test data is in seed.sql).

CREATE DATABASE IF NOT EXISTS events_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE events_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS venues;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------
-- users
-- ---------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL,
  phone VARCHAR(20) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','organizer','participant') NOT NULL DEFAULT 'participant',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- venues
-- ---------------------------------------------------------------
CREATE TABLE venues (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(150) NOT NULL,
  address     VARCHAR(255) NOT NULL,
  capacity    INT UNSIGNED NOT NULL,
  description TEXT NULL,
  PRIMARY KEY (id),
  CONSTRAINT chk_venues_capacity CHECK (capacity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- events
-- Rule enforced in PHP, not SQL: max_capacity <= venue capacity
-- ---------------------------------------------------------------
CREATE TABLE events (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title        VARCHAR(200) NOT NULL,
  description  TEXT NULL,
  event_date   DATE NOT NULL,
  event_time   TIME NOT NULL,
  venue_id     INT UNSIGNED NOT NULL,
  max_capacity INT UNSIGNED NOT NULL,
  organizer_id INT UNSIGNED NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_events_date (event_date),
  CONSTRAINT chk_events_capacity CHECK (max_capacity > 0),
  -- a venue used by events cannot be deleted
  CONSTRAINT fk_events_venue FOREIGN KEY (venue_id)
    REFERENCES venues (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  -- an organizer who still owns events cannot be deleted
  CONSTRAINT fk_events_organizer FOREIGN KEY (organizer_id)
    REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- registrations
-- Seats taken = pending + confirmed.
-- Waiting list order: registered_at ASC, then id ASC. The position is computed in queries, not stored.
-- ---------------------------------------------------------------
CREATE TABLE registrations (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id           INT UNSIGNED NOT NULL,
  event_id          INT UNSIGNED NOT NULL,
  status            ENUM('pending','confirmed','waitlisted','cancelled','rejected')
                    NOT NULL DEFAULT 'pending',
  registered_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_registrations_user_event (user_id, event_id),
  KEY idx_registrations_event_status (event_id, status),
  CONSTRAINT fk_registrations_user FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_registrations_event FOREIGN KEY (event_id)
    REFERENCES events (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- attendance (one row per registration, only for confirmed ones: checked in PHP)
-- ---------------------------------------------------------------
CREATE TABLE attendance (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  registration_id INT UNSIGNED NOT NULL,
  present         TINYINT(1) NOT NULL DEFAULT 0,
  marked_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  marked_by       INT UNSIGNED NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_attendance_registration (registration_id),
  CONSTRAINT fk_attendance_registration FOREIGN KEY (registration_id)
    REFERENCES registrations (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_attendance_marked_by FOREIGN KEY (marked_by)
    REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;