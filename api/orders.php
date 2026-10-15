<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../classes/Order.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$db = new Database();
$order = new Order($db);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$order_id = (int)($_POST['order_id'] ?? $_GET['order_id'] ?? 0);

switch ($action) {
    case 'get':
        if ($order_id > 0) {
            $order_details = $order->getOrderById($order_id, isAdmin() ? null : $_SESSION['user_id']);
            if ($order_details) {
                echo json_encode([
                    'success' => true,
                    'order' => $order_details
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found']);
            }
        } else {
            $orders = $order->getOrdersByUser($_SESSION['user_id']);
            echo json_encode([
                'success' => true,
                'orders' => $orders
            ]);
        }
        break;

    case 'update_status':
        if ($order_id > 0 && isAdmin()) {
            $status = $_POST['status'] ?? '';
            if (in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
                if ($order->updateOrderStatus($order_id, $status)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Order status updated'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update order status'
                    ]);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid status']);
            }
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
        }
        break;

    case 'track':
        if ($order_id > 0) {
            $tracking_info = $order->getTrackingInfo($order_id);
            if ($tracking_info) {
                echo json_encode([
                    'success' => true,
                    'tracking' => $tracking_info
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Tracking information not found']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID required']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>