USE coolfreeze_db;

-- Run this ONCE on your existing database (new installs already get these from schema.local.sql).
-- The admin Services page shows and saves a Category and an Estimated Duration for each service.
-- XAMPP uses MariaDB, which understands IF NOT EXISTS. On plain MySQL, remove "IF NOT EXISTS" and run it once.
ALTER TABLE services
  ADD COLUMN IF NOT EXISTS category VARCHAR(50) NULL AFTER description,
  ADD COLUMN IF NOT EXISTS duration VARCHAR(30) NULL AFTER category;

-- Fill in the 5 starter services (only the ones that are still empty)
UPDATE services SET category = 'Cleaning',     duration = '1–2 hrs' WHERE name = 'AC Cleaning'       AND category IS NULL;
UPDATE services SET category = 'Repair',       duration = '2–3 hrs' WHERE name = 'AC Repair'         AND category IS NULL;
UPDATE services SET category = 'Maintenance',  duration = '1 hr'    WHERE name = 'AC Maintenance'    AND category IS NULL;
UPDATE services SET category = 'Installation', duration = '3–5 hrs' WHERE name = 'AC Installation'   AND category IS NULL;
UPDATE services SET category = 'Repair',       duration = '2–4 hrs' WHERE name = 'Parts Replacement' AND category IS NULL;