USE coolfreeze_db;

-- Run this ONCE on your existing database (new installs already get these from schema.local.sql).
-- Needed by the admin Profile page (photo, password date, security settings, login history).
-- XAMPP uses MariaDB, which understands IF NOT EXISTS. On plain MySQL, remove "IF NOT EXISTS" from the ALTERs and run once.

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS password_changed_at DATETIME NULL AFTER last_login_at;

ALTER TABLE admins
  ADD COLUMN IF NOT EXISTS profile_image        VARCHAR(255) NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS two_factor           TINYINT(1)   NOT NULL DEFAULT 0  AFTER profile_image,
  ADD COLUMN IF NOT EXISTS failed_login_protect TINYINT(1)   NOT NULL DEFAULT 1  AFTER two_factor,
  ADD COLUMN IF NOT EXISTS auto_logout          TINYINT(1)   NOT NULL DEFAULT 0  AFTER failed_login_protect,
  ADD COLUMN IF NOT EXISTS logout_after         SMALLINT UNSIGNED NOT NULL DEFAULT 30 AFTER auto_logout;

CREATE TABLE IF NOT EXISTS login_history (
  history_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  status      ENUM('Success','Failed') NOT NULL,
  device      VARCHAR(60)  NULL,
  ip_address  VARCHAR(45)  NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (history_id),
  KEY idx_login_user (user_id, created_at),
  CONSTRAINT fk_login_user FOREIGN KEY (user_id)
    REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Speeds up the Analytics page date filters.
ALTER TABLE service_requests
  ADD INDEX IF NOT EXISTS idx_requests_submitted (submitted_at);
