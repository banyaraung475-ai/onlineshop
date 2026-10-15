<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json');

if (!ENABLE_WISHLIST) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Wishlist is disabled']);
    exit();
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = new Database();
$user_id = $_SESSION['user_id'] ?? null;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($product_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
            break;
        }

        if (!$db->exists('products', 'product_id = ?', [$product_id])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            break;
        }

        if ($db->exists('wishlist', 'user_id = ? AND product_id = ?', [$user_id, $product_id])) {
            echo json_encode(['success' => true, 'message' => 'Already in your wishlist']);
            break;
        }

        $db->insert('wishlist', [
            'user_id' => $user_id,
            'product_id' => $product_id
        ]);

        echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
        break;

    case 'remove':
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($product_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
            break;
        }

        $db->delete('wishlist', 'user_id = ? AND product_id = ?', [$user_id, $product_id]);
        echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
        break;

    case 'count':
        $count = $db->count('wishlist', 'user_id = ?', [$user_id]);
        echo json_encode(['success' => true, 'count' => $count]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>