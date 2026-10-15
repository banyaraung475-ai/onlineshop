<?php
// Note: redirect() function is already defined in config.php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../classes/Cart.php';

// Initialize Database and Cart
$db = new Database();
$cart = new Cart($db);

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function getCartCount() {
    global $cart;
    if (isset($cart)) {
        return $cart->getItemCount();
    }
    return 0;
}

function formatCurrency($amount) {
    return CURRENCY . number_format($amount, 2);
}

function createNotification($user_id, $title, $message, $type = 'info') {
    global $db;
    return $db->insert('notifications', [
        'user_id' => $user_id,
        'title' => $title,
        'message' => $message,
        'type' => $type
    ]);
}

function getCartTotal(){
    $total = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    }
    return $total;  
}
?>