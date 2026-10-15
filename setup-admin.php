<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';

// Create database connection
$db = new Database();

try {
    // Insert default admin user
    $adminData = [
        'username' => 'admin',
        'email' => 'admin@onlineshop.com',
        'password_hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'full_name' => 'System Administrator',
        'role' => 'admin'
    ];
    
    // Check if admin user already exists
    $existingAdmin = $db->fetchOne("SELECT user_id FROM users WHERE username = 'admin' OR email = 'admin@onlineshop.com'");
    
    if ($existingAdmin) {
        echo "Admin user already exists. Skipping insertion.\n";
    } else {
        $adminId = $db->insert('users', $adminData);
        
        if ($adminId) {
            echo "✅ Default admin user created successfully!\n";
            echo "Username: admin\n";
            echo "Email: admin@onlineshop.com\n";
            echo "Password: password\n";
            echo "Login at: http://localhost/onlineshop/login.php\n";
        } else {
            echo "❌ Failed to create admin user.\n";
        }
    }
    
    // Verify admin user was created
    $adminCheck = $db->fetchOne("SELECT * FROM users WHERE username = 'admin'");
    if ($adminCheck) {
        echo "\n✅ Admin user verification successful:\n";
        echo "User ID: " . $adminCheck['user_id'] . "\n";
        echo "Username: " . $adminCheck['username'] . "\n";
        echo "Email: " . $adminCheck['email'] . "\n";
        echo "Role: " . $adminCheck['role'] . "\n";
        echo "Created: " . $adminCheck['created_at'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
