<?php
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/../includes/Database.php';

class Cart {
    private $db;
    private $userId;
    private $tablesCache;
    private $columnsCache = [];

    public function __construct($db, $userId = null) {
        $this->db = $db;
        $this->userId = $userId;
        $this->tablesCache = null;
        $_SESSION['cart'] = $_SESSION['cart'] ?? [];
    }

    private function getTablesSafe() {
        if ($this->tablesCache !== null) {
            return $this->tablesCache;
        }

        try {
            $this->tablesCache = $this->db->getTables();
        } catch (Exception $e) {
            $this->tablesCache = [];
        }

        return $this->tablesCache;
    }

    private function getTableColumnsSafe($table) {
        if (isset($this->columnsCache[$table])) {
            return $this->columnsCache[$table];
        }

        if (!in_array($table, $this->getTablesSafe(), true)) {
            $this->columnsCache[$table] = [];
            return $this->columnsCache[$table];
        }

        try {
            $info = $this->db->getTableInfo($table);
            $this->columnsCache[$table] = array_column($info, 'Field');
        } catch (Exception $e) {
            $this->columnsCache[$table] = [];
        }

        return $this->columnsCache[$table];
    }

    private function hasColumn($table, $column) {
        return in_array($column, $this->getTableColumnsSafe($table), true);
    }

    public function addItem($product_id, $quantity = 1) {
        if (!isLoggedIn()) {
            return ['success' => false, 'message' => 'User not logged in.'];
        }
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Invalid quantity.'];
        }
        $product = $this->getProductDetails($product_id);
        if ($product && $product['stock_quantity'] >= $quantity) {
            if (isset($_SESSION['cart'][$product_id])) {
                $new_quantity = $_SESSION['cart'][$product_id]['quantity'] + $quantity;
                if ($product['stock_quantity'] < $new_quantity) {
                    return ['success' => false, 'message' => 'Insufficient stock.'];
                }
                $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'id' => $product['product_id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $quantity,
                    'image' => $product['image_url'],
                    'stock' => $product['stock_quantity']
                ];
            }
            return ['success' => true, 'message' => 'Product added to cart.'];
        }
        return ['success' => false, 'message' => 'Product not available or insufficient stock.'];
    }

    public function updateItem($product_id, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($product_id);
        }

        if (isset($_SESSION['cart'][$product_id])) {
            $product = $this->getProductDetails($product_id);
            if ($product && $product['stock_quantity'] >= $quantity) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                $_SESSION['cart'][$product_id]['stock'] = $product['stock_quantity'];
                return true;
            }
        }
        return false;
    }

    public function removeItem($product_id) {
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            return true;
        }
        return false;
    }

    public function getTotal() {
        $total = 0;
        foreach (($_SESSION['cart'] ?? []) as $item) {
            if (isset($item['price'], $item['quantity'])) {
                $total += $item['price'] * $item['quantity'];
            }
        }
        return $total;
    }

    public function getTotalWithShipping($shipping_fee = 2000) {
        return $this->getTotal() + $shipping_fee;
    }

    public function getItemCount() {
        $count = 0;
        foreach (($_SESSION['cart'] ?? []) as $item) {
            if (isset($item['quantity'])) {
                $count += $item['quantity'];
            }
        }
        return $count;
    }

    public function clearCart() {
        $_SESSION['cart'] = [];
    }

    public function getItems() {
        return $_SESSION['cart'] ?? [];
    }

    public function checkout($user_id, $payment_method, $delivery_method, $address, $notes = '') {
        $cart = $_SESSION['cart'] ?? [];

        // Validate cart is not empty
        if (empty($cart)) {
            return ['success' => false, 'message' => 'Checkout failed: Your cart is empty.'];
        }

        // Validate stock before starting transaction
        foreach ($cart as $product_id => $item) {
            if (!is_array($item) || !isset($item['quantity'], $item['name'])) {
                continue; // Skip malformed items
            }
            $product = $this->getProductDetails($product_id);
            if (!$product || $product['stock_quantity'] < $item['quantity']) {
                $error_message = "Checkout failed: Insufficient stock for product '{$item['name']}'. Only {$product['stock_quantity']} left.";
                return ['success' => false, 'message' => $error_message];
            }
        }

        $this->db->beginTransaction();

        try {
            // Calculate shipping fee based on selected delivery zone/method
            $shippingFeeMap = [
                'InCityStandard' => 2000,
                'InCityExpress' => 5000,
                'FarAwayStandard' => 4000,
                'FarAwayExpress' => 7000,
                // Backward compatibility with legacy values
                'Standard' => 2000,
                'Express' => 5000
            ];
            $shipping_fee = $shippingFeeMap[$delivery_method] ?? 2000;
            $total = $this->getTotal() + $shipping_fee;
            $order_number = 'ORD' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Create order (schema compatible: only include existing columns)
            $orderData = ['user_id' => $user_id];
            if ($this->hasColumn('orders', 'order_number')) {
                $orderData['order_number'] = $order_number;
            }
            if ($this->hasColumn('orders', 'subtotal')) {
                $orderData['subtotal'] = $this->getTotal();
            }
            if ($this->hasColumn('orders', 'tax_amount')) {
                $orderData['tax_amount'] = 0;
            }
            if ($this->hasColumn('orders', 'shipping_amount')) {
                $orderData['shipping_amount'] = $shipping_fee;
            }
            if ($this->hasColumn('orders', 'discount_amount')) {
                $orderData['discount_amount'] = 0;
            }
            if ($this->hasColumn('orders', 'total_amount')) {
                $orderData['total_amount'] = $total;
            } elseif ($this->hasColumn('orders', 'total')) {
                $orderData['total'] = $total;
            }
            if ($this->hasColumn('orders', 'payment_method')) {
                $orderData['payment_method'] = $payment_method;
            }
            if ($this->hasColumn('orders', 'payment_status')) {
                $orderData['payment_status'] = 'pending';
            }
            if ($this->hasColumn('orders', 'shipping_address')) {
                $orderData['shipping_address'] = $address;
            } elseif ($this->hasColumn('orders', 'address')) {
                $orderData['address'] = $address;
            }
            if ($this->hasColumn('orders', 'status')) {
                $orderData['status'] = 'pending';
            }
            if ($this->hasColumn('orders', 'notes')) {
                $orderData['notes'] = $notes;
            }

            $order_id = $this->db->insert('orders', $orderData);
            
            if (!$order_id) {
                throw new Exception("Failed to create order record.");
            }

            // Add order items and update stock
            foreach ($cart as $product_id => $item) {
                if (!is_array($item) || !isset($item['price'], $item['quantity'], $item['name'])) {
                    continue; // Skip malformed items
                }
                $total_price = $item['price'] * $item['quantity'];
                $orderItemData = [
                    'order_id' => $order_id,
                    'product_id' => $product_id,
                    'quantity' => $item['quantity']
                ];
                if ($this->hasColumn('order_items', 'unit_price')) {
                    $orderItemData['unit_price'] = $item['price'];
                }
                if ($this->hasColumn('order_items', 'price')) {
                    $orderItemData['price'] = $item['price'];
                }
                if ($this->hasColumn('order_items', 'total_price')) {
                    $orderItemData['total_price'] = $total_price;
                }

                $this->db->insert('order_items', $orderItemData);

                if (!$this->updateProductStock($product_id, $item['quantity'])) {
                    throw new Exception("Failed to update stock for product '{$item['name']}'.");
                }
            }

            // Add payment record (if table exists)
            if (in_array('payments', $this->getTablesSafe(), true)) {
                $paymentData = ['order_id' => $order_id];
                if ($this->hasColumn('payments', 'amount')) {
                    $paymentData['amount'] = $total;
                }
                if ($this->hasColumn('payments', 'payment_method')) {
                    $paymentData['payment_method'] = $payment_method;
                } elseif ($this->hasColumn('payments', 'method')) {
                    $paymentData['method'] = $payment_method;
                }
                if ($this->hasColumn('payments', 'status')) {
                    $paymentData['status'] = 'pending';
                }

                try {
                    $this->db->insert('payments', $paymentData);
                } catch (Exception $e) {
                    // Keep checkout functional even if legacy triggers on payments are broken.
                    error_log("Checkout payment insert skipped for order {$order_id}: " . $e->getMessage());
                }
            }

            // Add delivery method (if table exists)
            if (in_array('delivery_methods', $this->getTablesSafe(), true)) {
                $isExpress = in_array($delivery_method, ['Express', 'InCityExpress', 'FarAwayExpress'], true);
                $delivery_date = date('Y-m-d H:i:s', strtotime('+' . ($isExpress ? '2' : '5') . ' days'));
                $deliveryData = [];
                if ($this->hasColumn('delivery_methods', 'order_id')) {
                    $deliveryData['order_id'] = $order_id;
                }
                if ($this->hasColumn('delivery_methods', 'method')) {
                    $deliveryData['method'] = $delivery_method;
                }
                if ($this->hasColumn('delivery_methods', 'address')) {
                    $deliveryData['address'] = $address;
                }
                if ($this->hasColumn('delivery_methods', 'estimated_delivery')) {
                    $deliveryData['estimated_delivery'] = $delivery_date;
                }
                if ($this->hasColumn('delivery_methods', 'status')) {
                    $deliveryData['status'] = 'preparing';
                }
                $this->db->insert('delivery_methods', $deliveryData);
            }

            $this->db->commit();
            $this->clearCart();
            return ['success' => true, 'order_id' => $order_id];
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Checkout error for user ID {$user_id}: " . $e->getMessage());
            return ['success' => false, 'message' => 'An unexpected error occurred during checkout. ' . $e->getMessage()];
        }
    }

    private function getProductDetails($product_id) {
        return $this->db->fetchOne("SELECT * FROM products WHERE product_id = ?", [$product_id]);
    }

    private function updateProductStock($product_id, $quantity) {
        $stmt = $this->db->query("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?", [$quantity, $product_id, $quantity]);
        return $stmt->rowCount() > 0;
    }
}
