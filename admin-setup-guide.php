<?php
// Manual SQL file for admin user insertion
// Copy this SQL and run it in phpMyAdmin or your MySQL client

$sql = "
-- Insert default admin user
INSERT INTO users (username, email, password_hash, full_name, role) 
VALUES ('admin', 'admin@onlineshop.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin')
ON DUPLICATE KEY UPDATE username = username;

-- Alternative: Update existing user to admin role
UPDATE users SET role = 'admin' WHERE username = 'admin' OR email = 'admin@onlineshop.com';

-- Verify admin user
SELECT * FROM users WHERE username = 'admin';
";

echo "<h2>OnlineShop Admin User Setup</h2>";
echo "<h3>Manual SQL Instructions</h3>";
echo "<p>Copy and paste the following SQL into phpMyAdmin or your MySQL client:</p>";
echo "<textarea style='width: 100%; height: 300px; font-family: monospace;'>" . htmlspecialchars($sql) . "</textarea>";

echo "<h3>Login Credentials</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 300px;'>";
echo "<tr><td><strong>Username:</strong></td><td>admin</td></tr>";
echo "<tr><td><strong>Email:</strong></td><td>admin@onlineshop.com</td></tr>";
echo "<tr><td><strong>Password:</strong></td><td>password</td></tr>";
echo "<tr><td><strong>Role:</strong></td><td>admin</td></tr>";
echo "</table>";

echo "<h3>Access URLs</h3>";
echo "<ul>";
echo "<li><strong>Frontend:</strong> <a href='http://localhost/onlineshop/'>http://localhost/onlineshop/</a></li>";
echo "<li><strong>Admin Panel:</strong> <a href='http://localhost/onlineshop/admin.php'>http://localhost/onlineshop/admin.php</a></li>";
echo "<li><strong>Login:</strong> <a href='http://localhost/onlineshop/login.php'>http://localhost/onlineshop/login.php</a></li>";
echo "</ul>";

echo "<h3>Steps to Setup Admin User:</h3>";
echo "<ol>";
echo "<li>Open phpMyAdmin (usually at http://localhost/phpmyadmin/)</li>";
echo "<li>Select the 'online_shop' database</li>";
echo "<li>Click on the 'SQL' tab</li>";
echo "<li>Copy and paste the SQL from the box above</li>";
echo "<li>Click 'Go' to execute the SQL</li>";
echo "<li>Login with the credentials above</li>";
echo "</ol>";

echo "<div style='background: #f0f8ff; padding: 10px; margin: 10px 0; border-left: 4px solid #0066cc;'>";
echo "<strong>Note:</strong> The password hash corresponds to the plain password 'password'. ";
echo "You can change this after logging in through the admin panel.";
echo "</div>";
?>
