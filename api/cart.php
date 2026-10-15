<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../classes/Cart.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$db = new Database();
$user_id = $_SESSION['user_id'] ?? null;
$cart = new Cart($db, $user_id);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
  // Path: c:\wamp64\www\onlineshop\api\cart.php
// Modify the `case 'add':` section to handle the new return format from the `addItem` method.

// Replace the existing `case 'add':` block with this code:

      case 'add':
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        $result = $cart->addItem($product_id, $quantity);
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => $result['message'],
                'cart_count' => $cart->getItemCount(),
                'cart_total' => $cart->getTotal()
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['message']
            ]);
        }
        break;
        
    case 'update':
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        
        if ($cart->updateItem($product_id, $quantity)) {
            echo json_encode([
                'success' => true,
                'message' => 'Cart updated',
                'cart_count' => $cart->getItemCount(),
                'cart_total' => $cart->getTotal()
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update cart'
            ]);
        }
        break;

    case 'remove':
        $product_id = (int)$_POST['product_id'];
        
        if ($cart->removeItem($product_id)) {
            echo json_encode([
                'success' => true,
                'message' => 'Product removed from cart',
                'cart_count' => $cart->getItemCount(),
                'cart_total' => $cart->getTotal()
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to remove product from cart'
            ]);
        }
        break;

    case 'clear':
        $cart->clearCart();
        echo json_encode([
            'success' => true,
            'message' => 'Cart cleared',
            'cart_count' => 0,
            'cart_total' => 0
        ]);
        break;

    case 'get':
        $items = $cart->getItems();
        echo json_encode([
            'success' => true,
            'items' => $items,
            'cart_count' => $cart->getItemCount(),
            'cart_total' => $cart->getTotal()
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>