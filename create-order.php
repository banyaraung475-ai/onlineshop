<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    
    try {
        $db->beginTransaction();
        
        // Get cart items
        $cartItems = $db->fetchAll("
            SELECT ci.*, p.stock_quantity, p.name, p.price
            FROM cart_items ci
            LEFT JOIN products p ON ci.product_id = p.product_id
            WHERE ci.user_id = ?
        ", [$_SESSION['user_id']]);
        
        if (empty($cartItems)) {
            echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
            exit;
        }
        
        // Check stock availability
        foreach ($cartItems as $item) {
            if ($item['quantity'] > $item['stock_quantity']) {
                echo json_encode(['success' => false, 'message' => 'Not enough stock for ' . $item['name']]);
                exit;
            }
        }
        
        // Create order
        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            $subtotal += ((float)$item['price']) * ((int)$item['quantity']);
        }

        $shippingMethod = $_POST['shipping_method'] ?? 'Standard';
        $shippingMethod = in_array($shippingMethod, ['Standard', 'Express'], true) ? $shippingMethod : 'Standard';
        $shippingAmount = $shippingMethod === 'Express' ? 5000.0 : 2000.0;

        $paymentMethod = $_POST['payment_method'] ?? 'cod';
        $paymentMethodMap = [
            'KPay' => 'kpay',
            'AyaPay' => 'ayapay',
            'CashOnDelivery' => 'cod'
        ];
        if (isset($paymentMethodMap[$paymentMethod])) {
            $paymentMethod = $paymentMethodMap[$paymentMethod];
        }

        $orderNumber = 'ORD' . date('Ymd') . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        $addressParts = [
            trim($_POST['address'] ?? ''),
            trim($_POST['city'] ?? ''),
            trim($_POST['postal_code'] ?? '')
        ];
        $shippingAddress = implode(', ', array_filter($addressParts, static function ($value) {
            return $value !== '';
        }));

        $orderData = [
            'user_id' => $_SESSION['user_id'],
            'order_number' => $orderNumber,
            'subtotal' => $subtotal,
            'tax_amount' => 0,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => 0,
            'total_amount' => $subtotal + $shippingAmount,
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'shipping_address' => $shippingAddress,
            'status' => 'pending',
            'notes' => $_POST['notes'] ?? ''
        ];
        
        $orderId = $db->insert('orders', $orderData);
        
        if (!$orderId) {
            throw new Exception('Failed to create order');
        }
        
        // Create order items and update stock
        foreach ($cartItems as $item) {
            $orderItemData = [
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'total_price' => $item['price'] * $item['quantity']
            ];
            
            $db->insert('order_items', $orderItemData);
            
            // Update product stock
            $db->query("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?", 
                [$item['quantity'], $item['product_id']]);
        }
        
        // Create delivery record
        $deliveryData = [
            'order_id' => $orderId,
            'method' => $shippingMethod,
            'address' => $orderData['shipping_address'],
            'status' => 'preparing',
            'estimated_delivery' => date('Y-m-d H:i:s', strtotime('+3 days'))
        ];
        
        $db->insert('delivery_methods', $deliveryData);
        
        // Clear cart
        $db->delete('cart_items', 'user_id = ?', [$_SESSION['user_id']]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true, 
            'order_id' => $orderId,
            'message' => 'Order placed successfully'
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to place order: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
