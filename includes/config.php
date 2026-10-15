<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'onlineshop');

// Site configuration
define('SITE_NAME', 'OnlineShop Myanmar');
define('SITE_DESCRIPTION', 'Best online shopping platform in Myanmar with fast delivery and secure payments');
define('SITE_URL', 'http://localhost/onlineshop');
define('CURRENCY', 'Ks');
define('CURRENCY_SYMBOL', 'Ks');

// Admin configuration
define('ADMIN_EMAIL', 'admin@onlineshop.com');
define('SUPPORT_EMAIL', 'support@onlineshop.com');
define('SUPPORT_PHONE', '+95 9 123 456 789');

// Payment gateways
define('KPAY_MERCHANT_ID', 'your_kpay_merchant_id');
define('KPAY_SECRET_KEY', 'your_kpay_secret_key');
define('AYAPAY_MERCHANT_ID', 'your_ayapay_merchant_id');
define('AYAPAY_SECRET_KEY', 'your_ayapay_secret_key');

// Shipping configuration
define('FREE_SHIPPING_THRESHOLD', 50000);
define('DEFAULT_SHIPPING_COST', 5000);
define('EXPRESS_SHIPPING_COST', 15000);

// Tax configuration
define('TAX_RATE', 0.05);

// Security settings
define('HASH_ALGO', 'sha256');
define('SESSION_LIFETIME', 86400); // 24 hours

// Features
define('ENABLE_REVIEWS', true);
define('ENABLE_WISHLIST', true);
define('ENABLE_NEWSLETTER', true);
define('AUTO_APPROVE_REVIEWS', true);

// Social media
define('FACEBOOK_URL', 'https://facebook.com/onlineshop');
define('INSTAGRAM_URL', 'https://instagram.com/onlineshop');
define('TWITTER_URL', 'https://twitter.com/onlineshop');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 20);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Yangon');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function formatPrice($price) {
    return number_format($price, 0) . ' ' . CURRENCY_SYMBOL;
}

function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

function generateOrderNumber() {
    return 'ORD' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function generateTrackingNumber() {
    return 'TRK' . strtoupper(uniqid());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function asset($path) {
    return SITE_URL . '/' . ltrim($path, '/');
}

?>