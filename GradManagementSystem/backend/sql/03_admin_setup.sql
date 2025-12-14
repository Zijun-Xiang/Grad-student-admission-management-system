-- Admin role setup (optional) + create an initial admin user.
-- Run in MySQL Workbench on database: grad_system

-- 1) If your `users.role` column is an ENUM and does NOT include 'admin', uncomment ONE of the following:
-- ALTER TABLE users MODIFY role ENUM('student','faculty','admin') NOT NULL;
-- (If your role column is already VARCHAR, you do not need to change anything.)

-- 2) Create an admin account.
-- Option A (if users table has `email` column):
INSERT INTO users (username, password, role, email)
SELECT 'admin', '123456', 'admin', 'admin@example.com'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');

-- Option B (if users table does NOT have `email` column), comment out Option A and use this instead:
-- INSERT INTO users (username, password, role)
-- SELECT 'admin', '123456', 'admin'
-- WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');

-- Note: the system supports plaintext passwords and will auto-upgrade them to a secure hash on first login.
