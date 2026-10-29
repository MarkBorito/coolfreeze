USE coolfreeze_db;

-- Run this ONCE on your existing database (new installs already get these from schema.local.sql).
-- The New Technician form saves these extra details.
-- XAMPP uses MariaDB, which understands IF NOT EXISTS. On plain MySQL, remove "IF NOT EXISTS" and run it once.
ALTER TABLE technicians
  ADD COLUMN IF NOT EXISTS specialty   VARCHAR(100) NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS address     VARCHAR(255) NULL AFTER specialty,
  ADD COLUMN IF NOT EXISTS work_days   VARCHAR(60)  NULL AFTER address,
  ADD COLUMN IF NOT EXISTS work_hours  VARCHAR(40)  NULL AFTER work_days;