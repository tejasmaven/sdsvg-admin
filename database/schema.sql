-- SDSVG Community Directory — Database Schema
-- MySQL 8.0+ | InnoDB | utf8mb4_unicode_ci
-- Run this once before using the application.

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- genders (lookup)
CREATE TABLE IF NOT EXISTS genders (
  gender_id     INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(50)  NOT NULL UNIQUE,
  display_order INT          NOT NULL DEFAULT 0,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- relationships (lookup)
CREATE TABLE IF NOT EXISTS relationships (
  relationship_id INT AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(100) NOT NULL UNIQUE,
  display_order   INT          NOT NULL DEFAULT 0,
  is_active       TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- groups (one row per family)
CREATE TABLE IF NOT EXISTS `groups` (
  group_id              INT AUTO_INCREMENT PRIMARY KEY,
  address               TEXT,
  communication_address TEXT,
  is_in_surat           TINYINT(1) NOT NULL DEFAULT 1,
  created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration for existing installs:
-- ALTER TABLE `groups`
--   ADD COLUMN communication_address TEXT NULL AFTER address,
--   ADD COLUMN is_in_surat TINYINT(1) NOT NULL DEFAULT 1 AFTER communication_address;

-- members (one row per person)
CREATE TABLE IF NOT EXISTS members (
  member_id       INT AUTO_INCREMENT PRIMARY KEY,
  group_id        INT           NOT NULL,
  type            ENUM('P','S') NOT NULL,
  title           VARCHAR(20),
  first_name      VARCHAR(100)  NOT NULL,
  middle_name     VARCHAR(100),
  last_name       VARCHAR(100)  NOT NULL,
  dob             DATE          NULL DEFAULT NULL,
  gender_id       INT           NOT NULL,
  relationship_id INT           NOT NULL,
  mobile          VARCHAR(100),
  email           VARCHAR(150),
  education       VARCHAR(200),
  display_order   INT          NOT NULL DEFAULT 0,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id)        REFERENCES `groups`(group_id)              ON DELETE CASCADE,
  FOREIGN KEY (gender_id)       REFERENCES genders(gender_id),
  FOREIGN KEY (relationship_id) REFERENCES relationships(relationship_id),
  INDEX idx_members_last_name (last_name),
  INDEX idx_members_group_id  (group_id),
  INDEX idx_members_mobile    (mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- admin_users
CREATE TABLE IF NOT EXISTS admin_users (
  admin_id      INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(80)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  last_login    DATETIME,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- login_attempts (brute-force tracking)
CREATE TABLE IF NOT EXISTS login_attempts (
  attempt_id   INT AUTO_INCREMENT PRIMARY KEY,
  ip_address   VARCHAR(45)  NOT NULL,
  attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- edit_requests (public-portal change requests, held for admin review)
CREATE TABLE IF NOT EXISTS edit_requests (
  request_id      INT AUTO_INCREMENT PRIMARY KEY,
  target_type     ENUM('member','group') NOT NULL,     -- member fields vs family address
  group_id        INT           NOT NULL,               -- family the request belongs to
  member_id       INT           NULL,                   -- set when target_type = 'member'
  original_json   LONGTEXT      NULL,                   -- snapshot of current values (for diff)
  payload_json    LONGTEXT      NOT NULL,               -- proposed new values {field: value}
  status          ENUM('pending','approved','rejected','partial') NOT NULL DEFAULT 'pending',
  -- request metadata (tracking)
  ip_address      VARCHAR(45)   NULL,
  user_agent      VARCHAR(255)  NULL,
  accept_language VARCHAR(100)  NULL,
  referer         VARCHAR(255)  NULL,
  review_note     TEXT          NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at     DATETIME NULL,
  FOREIGN KEY (group_id)  REFERENCES `groups`(group_id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(member_id)  ON DELETE CASCADE,
  INDEX idx_edit_requests_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- public_throttle (rate-limiting for the public portal: verify + edit-submit)
CREATE TABLE IF NOT EXISTS public_throttle (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  ip_address   VARCHAR(45) NOT NULL,
  action       VARCHAR(20) NOT NULL,              -- e.g. 'verify', 'edit'
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_public_throttle (ip_address, action, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- pdf_settings (key-value store)
CREATE TABLE IF NOT EXISTS pdf_settings (
  setting_key   VARCHAR(100) PRIMARY KEY,
  setting_value LONGTEXT,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
