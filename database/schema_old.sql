CREATE DATABASE IF NOT EXISTS coolfreeze_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE coolfreeze_db;

CREATE TABLE IF NOT EXISTS customers (
    customer_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username         VARCHAR(50)  NOT NULL,
    email            VARCHAR(100) NOT NULL,
    phone            VARCHAR(20)  NULL,
    password_hash    VARCHAR(255) NOT NULL,
    agreed_to_terms  TINYINT(1) NOT NULL DEFAULT 0,
    terms_agreed_at  DATETIME NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_customers_username (username),
    UNIQUE KEY uq_customers_email (email)
) ENGINE=InnoDB;

