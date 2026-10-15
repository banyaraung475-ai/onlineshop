-- OnlineShop Admin User Setup SQL
-- Run this in phpMyAdmin or your MySQL client

-- Insert default admin user
INSERT INTO users (username, email, password_hash, full_name, role) 
VALUES ('admin', 'admin@onlineshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin')
ON DUPLICATE KEY UPDATE username = username;

-- Alternative: Update existing user to admin role (if user already exists)
UPDATE users SET role = 'admin' WHERE username = 'admin' OR email = 'admin@onlineshop.com';

-- Add role column if it doesn't exist (compatible with older MySQL)
SET @role_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'role'
);
SET @role_sql := IF(@role_exists = 0,
    'ALTER TABLE users ADD COLUMN role ENUM(\'customer\', \'admin\') DEFAULT \'customer\'',
    'SELECT 1'
);
PREPARE role_stmt FROM @role_sql;
EXECUTE role_stmt;
DEALLOCATE PREPARE role_stmt;

-- Set admin role for admin user
UPDATE users SET role = 'admin' WHERE username = 'admin';

-- Verify admin user creation
SELECT * FROM users WHERE username = 'admin';

-- Check all users with admin role
SELECT user_id, username, email, full_name, role, created_at FROM users WHERE role = 'admin';
