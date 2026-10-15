<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return $this->isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    public function login($username, $password, $remember = false) {
        try {
            // Get user by username or email
            $user = $this->db->fetchOne(
                "SELECT user_id, username, email, password_hash, full_name, role, is_active FROM users WHERE username = ? OR email = ?",
                [$username, $username]
            );
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Your account has been deactivated'];
            }
            
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            // Set session data
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            // Update last login
            $this->db->update('users', [
                'last_login' => date('Y-m-d H:i:s')
            ], 'user_id = ?', [$user['user_id']]);
            
            // Set remember me cookie if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60); // 30 days
                
                setcookie('remember_token', $token, $expires, '/', '', true, true);
                
                // Store token in database (you'd need a remember_tokens table)
                // For now, we'll skip this for simplicity
            }
            
            return [
                'success' => true, 
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['user_id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    public function logout() {
        // Clear session
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
        
        // Clear remember me cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        
        return true;
    }
    
    public function register($userData) {
        try {
            $username = sanitizeInput($userData['username']);
            $email = sanitizeInput($userData['email']);
            $password = $userData['password'];
            $full_name = sanitizeInput($userData['full_name'] ?? '');
            $address = sanitizeInput($userData['address'] ?? '');
            $phone = sanitizeInput($userData['phone'] ?? '');
            
            // Validation
            if (empty($username) || empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Username, email, and password are required'];
            }
            
            if (!validateEmail($email)) {
                return ['success' => false, 'message' => 'Invalid email address'];
            }
            
            if (strlen($password) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters long'];
            }
            
            // Check if user already exists
            if ($this->db->exists('users', 'username = ? OR email = ?', [$username, $email])) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $userId = $this->db->insert('users', [
                'username' => $username,
                'email' => $email,
                'password_hash' => $password_hash,
                'full_name' => $full_name,
                'address' => $address,
                'phone' => $phone,
                'role' => 'customer',
                'is_active' => 1,
                'email_verified' => 0
            ]);
            
            if ($userId) {
                return [
                    'success' => true, 
                    'message' => 'Registration successful! Welcome to ' . SITE_NAME,
                    'user_id' => $userId,
                    'auto_logged_in' => false
                ];
            } else {
                return ['success' => false, 'message' => 'Registration failed. Please try again.'];
            }
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    public function updateProfile($userId, $userData) {
        try {
            $allowedFields = ['full_name', 'email', 'address', 'phone'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($userData[$field])) {
                    $updateData[$field] = sanitizeInput($userData[$field]);
                }
            }
            
            if (empty($updateData)) {
                return ['success' => false, 'message' => 'No valid data provided'];
            }
            
            // Check if email is being changed and if it's already taken
            if (isset($updateData['email'])) {
                $existingUser = $this->db->fetchOne(
                    "SELECT user_id FROM users WHERE email = ? AND user_id != ?",
                    [$updateData['email'], $userId]
                );
                
                if ($existingUser) {
                    return ['success' => false, 'message' => 'Email address is already in use'];
                }
            }
            
            $result = $this->db->update('users', $updateData, 'user_id = ?', [$userId]);
            
            if ($result) {
                // Update session data if email or full_name changed
                if (isset($updateData['email'])) {
                    $_SESSION['email'] = $updateData['email'];
                }
                if (isset($updateData['full_name'])) {
                    $_SESSION['full_name'] = $updateData['full_name'];
                }
                
                return ['success' => true, 'message' => 'Profile updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update profile'];
            }
            
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update profile'];
        }
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current user
            $user = $this->db->fetchOne(
                "SELECT password_hash FROM users WHERE user_id = ?",
                [$userId]
            );
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Validate new password
            if (strlen($newPassword) < 6) {
                return ['success' => false, 'message' => 'New password must be at least 6 characters long'];
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $result = $this->db->update('users', [
                'password_hash' => $newPasswordHash
            ], 'user_id = ?', [$userId]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Password changed successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to change password'];
            }
            
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to change password'];
        }
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            redirect('login.php');
            exit();
        }
    }
    
    public function requireAdmin() {
        if (!$this->isAdmin()) {
            redirect('index.php');
            exit();
        }
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'full_name' => $_SESSION['full_name'],
                'role' => $_SESSION['user_role']
            ];
        }
        return null;
    }
    
    public function checkSessionTimeout() {
        if ($this->isLoggedIn()) {
            $timeout = SESSION_LIFETIME;
            $lastActivity = $_SESSION['last_activity'] ?? 0;
            
            if (time() - $lastActivity > $timeout) {
                $this->logout();
                return false;
            }
            
            $_SESSION['last_activity'] = time();
            return true;
        }
        return false;
    }
}

// Create auth instance
$auth = new Auth();

// Check session timeout on every request
$auth->checkSessionTimeout();

// Legacy functions for backward compatibility
// Note: isLoggedIn() and isAdmin() are already defined in config.php

function login($username, $password) {
    global $auth;
    $result = $auth->login($username, $password);
    return $result['success'];
}

function logout() {
    global $auth;
    return $auth->logout();
}

function register($username, $password, $email, $full_name = '', $address = '', $phone = '') {
    global $auth;
    $userData = compact('username', 'password', 'email', 'full_name', 'address', 'phone');
    $result = $auth->register($userData);
    return $result['success'];
}
?>
