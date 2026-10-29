USE coolfreeze_db;

-- Sample technicians so the admin can assign someone when accepting a request.
-- Password for all of them: Password123 (same hash as the sample admin)
INSERT IGNORE INTO users (role, username, email, password_hash) VALUES
  ('technician', 'mikha.bernardo',  'mikha.bernardo@coolfreeze.com',  '$2b$12$m7OQpQQmOdVFXGq/hi3Qg.cEqviZwSqlBhtPXmVzAp37GCqbsZn7S'),
  ('technician', 'mark.borito',     'mark.borito@coolfreeze.com',     '$2b$12$m7OQpQQmOdVFXGq/hi3Qg.cEqviZwSqlBhtPXmVzAp37GCqbsZn7S'),
  ('technician', 'richmond.quizon', 'richmond.quizon@coolfreeze.com', '$2b$12$m7OQpQQmOdVFXGq/hi3Qg.cEqviZwSqlBhtPXmVzAp37GCqbsZn7S');

INSERT INTO technicians (user_id, full_name, phone, availability)
SELECT u.user_id, 'Bernardo, Mikha C.', '0917-100-0001', 'available' FROM users u WHERE u.username = 'mikha.bernardo'
  AND NOT EXISTS (SELECT 1 FROM technicians t WHERE t.user_id = u.user_id);
INSERT INTO technicians (user_id, full_name, phone, availability)
SELECT u.user_id, 'Borito, Mark Adrian A.', '0917-100-0002', 'available' FROM users u WHERE u.username = 'mark.borito'
  AND NOT EXISTS (SELECT 1 FROM technicians t WHERE t.user_id = u.user_id);
INSERT INTO technicians (user_id, full_name, phone, availability)
SELECT u.user_id, 'Quizon, Richmond A.', '0917-100-0003', 'off_duty' FROM users u WHERE u.username = 'richmond.quizon'
  AND NOT EXISTS (SELECT 1 FROM technicians t WHERE t.user_id = u.user_id);