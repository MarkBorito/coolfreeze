CREATE DATABASE IF NOT EXISTS coolfreeze_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE coolfreeze_db;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
  user_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role           ENUM('customer','admin','technician') NOT NULL,
  username       VARCHAR(50)  NOT NULL,
  email          VARCHAR(100) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  is_active      TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at  DATETIME     NULL,
  password_changed_at DATETIME NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
  customer_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id          INT UNSIGNED NOT NULL,
  phone            VARCHAR(20)  NOT NULL,
  full_name        VARCHAR(100) NULL,
  birthday         DATE         NULL,
  address          VARCHAR(255) NULL,
  profile_image    VARCHAR(255) NULL,
  agreed_to_terms  TINYINT(1)   NOT NULL DEFAULT 0,
  terms_agreed_at  DATETIME     NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (customer_id),
  UNIQUE KEY uq_customers_user (user_id),
  CONSTRAINT fk_customers_user FOREIGN KEY (user_id)
    REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admins (
  admin_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  full_name   VARCHAR(100) NOT NULL,
  phone       VARCHAR(20)  NULL,
  profile_image        VARCHAR(255) NULL,
  two_factor           TINYINT(1)   NOT NULL DEFAULT 0,
  failed_login_protect TINYINT(1)   NOT NULL DEFAULT 1,
  auto_logout          TINYINT(1)   NOT NULL DEFAULT 0,
  logout_after         SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (admin_id),
  UNIQUE KEY uq_admins_user (user_id),
  CONSTRAINT fk_admins_user FOREIGN KEY (user_id)
    REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS technicians (
  technician_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id        INT UNSIGNED NOT NULL,
  full_name      VARCHAR(100) NOT NULL,
  phone          VARCHAR(20)  NOT NULL,
  specialty      VARCHAR(100) NULL,
  address        VARCHAR(255) NULL,
  work_days      VARCHAR(60)  NULL,
  work_hours     VARCHAR(40)  NULL,
  profile_image  VARCHAR(255) NULL,
  availability   ENUM('available','on_job','off_duty') NOT NULL DEFAULT 'available',
  created_by     INT UNSIGNED NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (technician_id),
  UNIQUE KEY uq_technicians_user (user_id),
  CONSTRAINT fk_technicians_user FOREIGN KEY (user_id)
    REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_technicians_created_by FOREIGN KEY (created_by)
    REFERENCES admins (admin_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS services (
  service_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  description   VARCHAR(255) NULL,
  category      VARCHAR(50)  NULL,
  duration      VARCHAR(30)  NULL,
  icon          VARCHAR(50)  NULL,              
  image         VARCHAR(255) NULL,
  base_price    DECIMAL(10,2) NOT NULL DEFAULT 0.00,  
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  sort_order    SMALLINT     NOT NULL DEFAULT 0,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (service_id),
  UNIQUE KEY uq_services_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS service_inclusions (
  inclusion_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  service_id    INT UNSIGNED NOT NULL,
  label         VARCHAR(150) NOT NULL,
  sort_order    SMALLINT     NOT NULL DEFAULT 0,
  PRIMARY KEY (inclusion_id),
  KEY idx_inclusions_service (service_id),
  CONSTRAINT fk_inclusions_service FOREIGN KEY (service_id)
    REFERENCES services (service_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ac_unit_types (
  unit_type_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name              VARCHAR(50)  NOT NULL,
  price_adjustment  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  is_active         TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (unit_type_id),
  UNIQUE KEY uq_unit_types_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart_items (
  cart_item_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id    INT UNSIGNED NOT NULL,
  service_id     INT UNSIGNED NOT NULL,
  unit_type_id   INT UNSIGNED NOT NULL,
  customer_type  ENUM('Residential','Commercial') NOT NULL DEFAULT 'Residential',
  quantity       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  added_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (cart_item_id),
  KEY idx_cart_customer (customer_id),
  CONSTRAINT fk_cart_customer  FOREIGN KEY (customer_id)  REFERENCES customers (customer_id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_service   FOREIGN KEY (service_id)   REFERENCES services (service_id),
  CONSTRAINT fk_cart_unit_type FOREIGN KEY (unit_type_id) REFERENCES ac_unit_types (unit_type_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS service_requests (
  request_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id       INT UNSIGNED NOT NULL,
  status            ENUM('Pending','Confirmed','On going','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  status_detail     VARCHAR(60) NOT NULL DEFAULT 'Pending Evaluation',
  preferred_date    DATE         NOT NULL,
  preferred_time    VARCHAR(30)  NOT NULL,         
  service_address   VARCHAR(255) NOT NULL,
  notes             TEXT         NULL,
  contact_name      VARCHAR(100) NOT NULL,
  contact_phone     VARCHAR(20)  NOT NULL,
  estimated_total   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  final_total       DECIMAL(10,2) NULL,           
  reviewed_by       INT UNSIGNED NULL,            
  reviewed_at       DATETIME     NULL,
  scheduled_date    DATE         NULL,            
  scheduled_time    VARCHAR(30)  NULL,
  cancel_reason     VARCHAR(255) NULL,
  cancelled_by_type ENUM('customer','admin') NULL,
  started_at        DATETIME     NULL,            
  completed_at      DATETIME     NULL,
  submitted_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (request_id),
  KEY idx_requests_customer (customer_id),
  KEY idx_requests_status (status),
  KEY idx_requests_scheduled (scheduled_date),
  KEY idx_requests_submitted (submitted_at),
  CONSTRAINT fk_requests_customer FOREIGN KEY (customer_id) REFERENCES customers (customer_id),
  CONSTRAINT fk_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES admins (admin_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS request_items (
  item_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  request_id     INT UNSIGNED NOT NULL,
  service_id     INT UNSIGNED NOT NULL,
  unit_type_id   INT UNSIGNED NOT NULL,
  customer_type  ENUM('Residential','Commercial') NOT NULL DEFAULT 'Residential',
  quantity       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  unit_price     DECIMAL(10,2) NOT NULL,
  subtotal       DECIMAL(10,2) AS (unit_price * quantity) VIRTUAL,
  PRIMARY KEY (item_id),
  KEY idx_items_request (request_id),
  CONSTRAINT fk_items_request   FOREIGN KEY (request_id)   REFERENCES service_requests (request_id) ON DELETE CASCADE,
  CONSTRAINT fk_items_service   FOREIGN KEY (service_id)   REFERENCES services (service_id),
  CONSTRAINT fk_items_unit_type FOREIGN KEY (unit_type_id) REFERENCES ac_unit_types (unit_type_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS request_attachments (
  attachment_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  request_id     INT UNSIGNED NOT NULL,
  file_path      VARCHAR(255) NOT NULL,
  uploaded_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (attachment_id),
  KEY idx_attach_request (request_id),
  CONSTRAINT fk_attach_request FOREIGN KEY (request_id) REFERENCES service_requests (request_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS request_technicians (
  assignment_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  request_id     INT UNSIGNED NOT NULL,
  technician_id  INT UNSIGNED NOT NULL,
  is_lead        TINYINT(1) NOT NULL DEFAULT 0,
  assigned_by    INT UNSIGNED NULL,
  assigned_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (assignment_id),
  UNIQUE KEY uq_assignment (request_id, technician_id),
  KEY idx_assign_technician (technician_id),
  CONSTRAINT fk_assign_request    FOREIGN KEY (request_id)    REFERENCES service_requests (request_id) ON DELETE CASCADE,
  CONSTRAINT fk_assign_technician FOREIGN KEY (technician_id) REFERENCES technicians (technician_id),
  CONSTRAINT fk_assign_admin      FOREIGN KEY (assigned_by)   REFERENCES admins (admin_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS request_status_history (
  history_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  request_id     INT UNSIGNED NOT NULL,
  old_status     VARCHAR(20) NULL,
  new_status     VARCHAR(20) NOT NULL,
  remarks        VARCHAR(255) NULL,
  changed_by_type ENUM('customer','admin','technician','system') NOT NULL,
  changed_by_id  INT UNSIGNED NULL,
  changed_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (history_id),
  KEY idx_history_request (request_id),
  CONSTRAINT fk_history_request FOREIGN KEY (request_id) REFERENCES service_requests (request_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS service_proofs (
  proof_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  request_id     INT UNSIGNED NOT NULL,
  service_notes  TEXT NULL,
  service_cost   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  receipt_no     VARCHAR(20) NULL,                  
  submitted_by   INT UNSIGNED NULL,              
  completed_on   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (proof_id),
  UNIQUE KEY uq_proof_request (request_id),
  UNIQUE KEY uq_proof_receipt (receipt_no),
  CONSTRAINT fk_proof_request    FOREIGN KEY (request_id)  REFERENCES service_requests (request_id) ON DELETE CASCADE,
  CONSTRAINT fk_proof_technician FOREIGN KEY (submitted_by) REFERENCES technicians (technician_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS service_proof_photos (
  photo_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  proof_id    INT UNSIGNED NOT NULL,
  file_path   VARCHAR(255) NOT NULL,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (photo_id),
  KEY idx_proof_photos (proof_id),
  CONSTRAINT fk_proof_photos FOREIGN KEY (proof_id) REFERENCES service_proofs (proof_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
  notification_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  recipient_type   ENUM('customer','admin','technician') NOT NULL,
  recipient_id     INT UNSIGNED NOT NULL,
  request_id       INT UNSIGNED NULL,
  title            VARCHAR(100) NOT NULL,
  message          VARCHAR(255) NOT NULL,
  is_read          TINYINT(1) NOT NULL DEFAULT 0,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (notification_id),
  KEY idx_notif_recipient (recipient_type, recipient_id, is_read),
  CONSTRAINT fk_notif_request FOREIGN KEY (request_id) REFERENCES service_requests (request_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
  reset_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_type     ENUM('customer','admin','technician') NOT NULL DEFAULT 'customer',
  email         VARCHAR(100) NOT NULL,
  code_hash     VARCHAR(255) NOT NULL,
  attempts      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  expires_at    DATETIME NOT NULL,
  used_at       DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (reset_id),
  KEY idx_reset_email (user_type, email)
) ENGINE=InnoDB;

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

CREATE TABLE IF NOT EXISTS activity_logs (
  log_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id     INT UNSIGNED NULL,
  action       VARCHAR(100) NOT NULL,         
  target_type  VARCHAR(50)  NULL,
  target_id    INT UNSIGNED NULL,
  details      VARCHAR(255) NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (log_id),
  KEY idx_logs_admin (admin_id),
  CONSTRAINT fk_logs_admin FOREIGN KEY (admin_id) REFERENCES admins (admin_id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;


INSERT IGNORE INTO ac_unit_types (name) VALUES
  ('Split Type'), ('Window Type'), ('Cassette Type'), ('Portable Type');

INSERT IGNORE INTO services (service_id, name, description, icon, base_price, sort_order) VALUES
  (1, 'AC Cleaning',       'Keep your aircon clean and efficient with our professional cleaning service',  'fa-fan',                 800,  1),
  (2, 'AC Repair',         'We fix aircon problems quickly and efficiently to get your unit back in condition', 'fa-screwdriver-wrench', 1000, 2),
  (3, 'AC Maintenance',    'Prevent problems before they happen with our scheduled maintenance service',   'fa-gear',                1200, 3),
  (4, 'AC Installation',   'Professional installation for your new aircon unit',                           'fa-wind',                800,  4),
  (5, 'Parts Replacement', 'Replace damaged parts with genuine and high-quality components',               'fa-toolbox',             800,  5);

INSERT IGNORE INTO service_inclusions (inclusion_id, service_id, label, sort_order) VALUES
  (1, 1, 'General cleaning', 1), (2, 1, 'Filter cleaning', 2), (3, 1, 'Unit inspection', 3), (4, 1, 'Basic performance check', 4),
  (5, 2, 'Diagnose the issue', 1), (6, 2, 'Repair faulty parts', 2), (7, 2, 'Test functionality', 3), (8, 2, 'Provide service report', 4),
  (9, 3, 'Full system check', 1), (10, 3, 'Clean and inspect components', 2), (11, 3, 'Optimize performance', 3), (12, 3, 'Extend unit lifespan', 4),
  (13, 4, 'Site inspection', 1), (14, 4, 'Proper unit installation', 2), (15, 4, 'System testing', 3), (16, 4, 'Warranty and support', 4),
  (17, 5, 'Genuine parts', 1), (18, 5, 'Professional installation', 2), (19, 5, 'System testing', 3), (20, 5, 'Warranty and replaced parts', 4);

