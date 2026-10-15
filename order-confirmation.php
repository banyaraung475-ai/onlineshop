<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'classes/Order.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: my-orders.php");
    exit();
}

$order_id = (int)$_GET['order_id'];
$user_id = $_SESSION['user_id'];

$db = new Database();
$order = new Order($db);

$orderDetails = $order->getOrderById($order_id, $user_id);

// Ensure the order belongs to the current user
if (!$orderDetails) {
    header("Location: my-orders.php");
    exit();
}

$page_title = "Order Confirmation";
include 'includes/header.php';

$items_total = 0.0;
if (!empty($orderDetails['items']) && is_array($orderDetails['items'])) {
    foreach ($orderDetails['items'] as $item) {
        if (isset($item['total_price'])) {
            $items_total += (float)$item['total_price'];
        } elseif (isset($item['quantity'], $item['unit_price'])) {
            $items_total += (float)$item['quantity'] * (float)$item['unit_price'];
        } elseif (isset($item['quantity'], $item['price'])) {
            $items_total += (float)$item['quantity'] * (float)$item['price'];
        }
    }
}
$shipping_fee = isset($orderDetails['shipping_fee']) ? (float)$orderDetails['shipping_fee'] : 0.0;
$order_total = null;
if (isset($orderDetails['total_amount'])) {
    $order_total = (float)$orderDetails['total_amount'];
} elseif (isset($orderDetails['total'])) {
    $order_total = (float)$orderDetails['total'];
} else {
    $order_total = $items_total + $shipping_fee;
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg text-center">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h1 class="h3 mb-3">Thank You For Your Order!</h1>
                    <p class="text-muted">Your order has been placed successfully. A confirmation email has been sent to you.</p>
                    <p class="fw-bold">Order Number: <?php echo htmlspecialchars($orderDetails['order_number']); ?></p>
                    
                    <div class="card mt-4 text-start">
                        <div class="card-header">
                            Order Summary
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach (($orderDetails['items'] ?? []) as $item): ?>
                                <?php
                                    $item_total = 0.0;
                                    if (isset($item['total_price'])) {
                                        $item_total = (float)$item['total_price'];
                                    } elseif (isset($item['quantity'], $item['unit_price'])) {
                                        $item_total = (float)$item['quantity'] * (float)$item['unit_price'];
                                    } elseif (isset($item['quantity'], $item['price'])) {
                                        $item_total = (float)$item['quantity'] * (float)$item['price'];
                                    }
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php echo htmlspecialchars($item['name'] ?? 'Product'); ?>
                                        <small class="d-block text-muted">Quantity: <?php echo (int)($item['quantity'] ?? 0); ?></small>
                                    </div>
                                    <span><?php echo number_format($item_total); ?> Ks</span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <span><?php echo number_format($items_total); ?> Ks</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Shipping:</span>
                                <span><?php echo number_format($shipping_fee); ?> Ks</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total:</span>
                                <span><?php echo number_format($order_total); ?> Ks</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="products.php" class="btn btn-outline-primary">Continue Shopping</a>
                        <a href="my-orders.php" class="btn btn-primary">View My Orders</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Unset the session variable to prevent re-showing the confirmation on refresh
unset($_SESSION['new_order']);
include 'includes/footer.php';
?>
