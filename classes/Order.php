<?php
require_once __DIR__ . '/../includes/Database.php';

class Order {
    private $db;
    private $tablesCache;
    private $columnsCache = [];

    public function __construct($db) {
        $this->db = $db;
    }

    private function hasColumn($table, $column) {
        return in_array($column, $this->getTableColumnsSafe($table), true);
    }

    /**
     * Get order details by ID with optional user verification
     * @param int $order_id The order ID to retrieve
     * @param int|null $user_id Optional user ID for verification
     * @return array|null Order details or null if not found
     */
    public function getOrderById($order_id, $user_id = null) {
        $orderColumns = $this->getTableColumnsSafe('orders');
        $selectParts = ['o.order_id'];

        if (in_array('order_number', $orderColumns, true)) {
            $selectParts[] = 'o.order_number';
        }
        if (in_array('status', $orderColumns, true)) {
            $selectParts[] = 'o.status';
        }
        if (in_array('total_amount', $orderColumns, true)) {
            $selectParts[] = 'o.total_amount';
        }
        if (in_array('created_at', $orderColumns, true)) {
            $selectParts[] = 'o.created_at';
        }
        if (in_array('shipping_address', $orderColumns, true)) {
            $selectParts[] = 'o.shipping_address';
        }
        if (
            in_array('delivery_methods', $this->getTablesSafe(), true) &&
            $this->hasColumn('delivery_methods', 'order_id') &&
            $this->hasColumn('delivery_methods', 'address')
        ) {
            $selectParts[] = "(SELECT address FROM delivery_methods WHERE order_id = o.order_id ORDER BY delivery_id DESC LIMIT 1) as delivery_address";
        }
        if (in_array('payment_method', $orderColumns, true)) {
            $selectParts[] = 'o.payment_method';
        }
        if (in_array('payment_status', $orderColumns, true)) {
            $selectParts[] = 'o.payment_status';
        }
        if (in_array('shipping_amount', $orderColumns, true)) {
            $selectParts[] = 'o.shipping_amount';
        }

        $selectParts[] = 'u.full_name as customer_name';
        $selectParts[] = 'u.username as customer_username';
        $selectParts[] = 'u.email as customer_email';
        $selectParts[] = 'u.phone as customer_phone';

        $query = "SELECT " . implode(', ', $selectParts) . "
                 FROM orders o
                 LEFT JOIN users u ON o.user_id = u.user_id
                 WHERE o.order_id = ?";
        
        $params = [$order_id];
        if ($user_id) {
            $query .= " AND o.user_id = ?";
            $params[] = $user_id;
        }
        
        $order = $this->db->fetchOne($query, $params);

        if ($order) {
            // Set customer_name to 'Guest Customer' if full_name is empty
            if (empty($order['customer_name'])) {
                $order['customer_name'] = 'Guest Customer';
            }
            if (empty($order['order_number'])) {
                $order['order_number'] = $order['order_id'];
            }
            if (empty($order['status'])) {
                $order['status'] = 'pending';
            }
            if (empty($order['payment_status'])) {
                $order['payment_status'] = $this->getPaymentStatusSafe($order_id);
            }
            if (empty($order['payment_method'])) {
                $order['payment_method'] = $this->getPaymentMethodSafe($order_id);
            }
            if (empty($order['delivery_address'])) {
                $shippingAddress = trim((string)($order['shipping_address'] ?? ''));
                if ($shippingAddress !== '') {
                    $order['delivery_address'] = $shippingAddress;
                } else {
                    $order['delivery_address'] = $this->getDeliveryAddressSafe($order_id);
                }
            }
            if (!isset($order['shipping_fee'])) {
                if (isset($order['shipping_amount'])) {
                    $order['shipping_fee'] = (float)$order['shipping_amount'];
                } elseif (defined('DEFAULT_SHIPPING_COST')) {
                    $order['shipping_fee'] = (float)DEFAULT_SHIPPING_COST;
                } else {
                    $order['shipping_fee'] = 0.0;
                }
            }
            $order['items'] = $this->getOrderItems($order_id);
        }
        
        return $order;
    }

    /**
     * Get all orders for a specific user
     * @param int $user_id The user ID
     * @return array Array of orders
     */
    public function getOrdersByUser($user_id) {
        $orderColumns = $this->getTableColumnsSafe('orders');
        $selectParts = ['o.order_id', 'o.user_id'];

        $candidateOrderColumns = [
            'order_number', 'status', 'subtotal', 'tax_amount', 'shipping_amount',
            'discount_amount', 'total_amount', 'total', 'currency', 'payment_method',
            'shipping_address', 'billing_address', 'tracking_number', 'notes', 'updated_at'
        ];
        foreach ($candidateOrderColumns as $column) {
            if (in_array($column, $orderColumns, true)) {
                $selectParts[] = "o.$column";
            }
        }

        if (in_array('created_at', $orderColumns, true)) {
            $selectParts[] = "o.created_at as order_date";
        } else {
            $selectParts[] = "NULL as order_date";
        }

        $orderItemColumns = $this->getTableColumnsSafe('order_items');
        if (
            in_array('order_items', $this->getTablesSafe(), true) &&
            in_array('order_id', $orderItemColumns, true) &&
            in_array('quantity', $orderItemColumns, true)
        ) {
            if (in_array('unit_price', $orderItemColumns, true)) {
                $selectParts[] = "(SELECT SUM(oi.quantity * oi.unit_price) FROM order_items oi WHERE oi.order_id = o.order_id) AS items_total";
            } elseif (in_array('price', $orderItemColumns, true)) {
                $selectParts[] = "(SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.order_id) AS items_total";
            } else {
                $selectParts[] = "0 AS items_total";
            }
        } else {
            $selectParts[] = "0 AS items_total";
        }

        $query = "SELECT " . implode(', ', $selectParts) . " FROM orders o";

        if (
            in_array('delivery_methods', $this->getTablesSafe(), true) &&
            $this->hasColumn('delivery_methods', 'order_id')
        ) {
            $deliverySelectParts = [];
            if ($this->hasColumn('delivery_methods', 'method')) {
                $deliverySelectParts[] = 'd.method AS delivery_method';
            }
            if ($this->hasColumn('delivery_methods', 'address')) {
                $deliverySelectParts[] = 'd.address AS delivery_address';
            }
            if ($this->hasColumn('delivery_methods', 'estimated_delivery')) {
                $deliverySelectParts[] = 'd.estimated_delivery';
            }
            if (!empty($deliverySelectParts)) {
                $query = str_replace(
                    " FROM orders o",
                    ", " . implode(', ', $deliverySelectParts) . " FROM orders o",
                    $query
                );
                $query .= " LEFT JOIN delivery_methods d ON o.order_id = d.order_id";
            }
        }

        if (
            in_array('payments', $this->getTablesSafe(), true) &&
            $this->hasColumn('payments', 'order_id')
        ) {
            $paymentSelectParts = [];
            if ($this->hasColumn('payments', 'status')) {
                $paymentSelectParts[] = 'p.status AS payment_status';
            }
            if ($this->hasColumn('payments', 'transaction_id')) {
                $paymentSelectParts[] = 'p.transaction_id';
            }
            if (!empty($paymentSelectParts)) {
                $query = str_replace(
                    " FROM orders o",
                    ", " . implode(', ', $paymentSelectParts) . " FROM orders o",
                    $query
                );
                $query .= " LEFT JOIN payments p ON o.order_id = p.order_id";
            }
        }

        $query .= " WHERE o.user_id = ?";
        if (in_array('created_at', $orderColumns, true)) {
            $query .= " ORDER BY o.created_at DESC";
        } else {
            $query .= " ORDER BY o.order_id DESC";
        }

        $rows = $this->db->fetchAll($query, [$user_id]);
        foreach ($rows as &$row) {
            if (!isset($row['status']) || $row['status'] === null || $row['status'] === '') {
                $row['status'] = 'pending';
            }
            if (!isset($row['items_total']) || $row['items_total'] === null) {
                $row['items_total'] = 0;
            }
            if (!isset($row['total_amount']) || $row['total_amount'] === null) {
                if (isset($row['total']) && $row['total'] !== null) {
                    $row['total_amount'] = (float)$row['total'];
                } else {
                    $shipping = isset($row['shipping_amount']) ? (float)$row['shipping_amount'] : 0.0;
                    $row['total_amount'] = (float)$row['items_total'] + $shipping;
                }
            }
            if (empty($row['order_number']) && isset($row['order_id'])) {
                $row['order_number'] = $row['order_id'];
            }
            if (empty($row['order_date'])) {
                $row['order_date'] = date('Y-m-d H:i:s');
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Get all items for a specific order
     * @param int $order_id The order ID
     * @return array Array of order items
     */
    public function getOrderItems($order_id) {
        $query = "SELECT oi.*, p.name, p.description, p.image_url AS image
                  FROM order_items oi
                  LEFT JOIN products p ON oi.product_id = p.product_id
                  WHERE oi.order_id = ?";
        
        return $this->db->fetchAll($query, [$order_id]);
    }

    /**
     * Update order status
     * @param int $order_id The order ID
     * @param string $status New status
     * @return bool True on success, false on failure
     */
    public function updateOrderStatus($order_id, $status) {
        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            error_log("Invalid status: $status");
            return false;
        }

        $order = $this->db->fetchOne(
            "SELECT user_id, order_number, status FROM orders WHERE order_id = ?",
            [$order_id]
        );
        if (!$order) {
            error_log("Could not find order {$order_id} for status update");
            return false;
        }

        // Skip no-op updates to prevent duplicate notifications.
        if ($order['status'] === $status) {
            return true;
        }

        $success = $this->db->update('orders', ['status' => $status], 'order_id = ?', [$order_id]);

        if ($success && in_array($status, ['processing', 'shipped', 'delivered'], true)) {
            $orderNumber = $order['order_number'] ?: $order_id;
            $labels = [
                'processing' => 'Processing',
                'shipped' => 'Shipped',
                'delivered' => 'Delivered'
            ];
            $notificationId = $this->db->insert('notifications', [
                'user_id' => $order['user_id'],
                'title' => "Order #{$orderNumber} update",
                'message' => "Your order #{$orderNumber} status is now {$labels[$status]}.",
                'type' => $status === 'delivered' ? 'success' : 'info'
            ]);
            if (!$notificationId) {
                error_log("Failed to create notification for order {$orderNumber}");
            }
        }

        return $success;
    }

    /**
     * Get tracking information for an order
     * @param int $order_id The order ID
     * @return array|null Tracking information
     */
    public function getTrackingInfo($order_id) {
        if (
            !in_array('delivery_methods', $this->getTablesSafe(), true) ||
            !$this->hasColumn('delivery_methods', 'order_id')
        ) {
            return null;
        }

        $orderTrackingColumn = $this->hasColumn('orders', 'tracking_number')
            ? 'o.tracking_number'
            : 'NULL AS tracking_number';
        $query = "SELECT d.*, o.status as order_status, o.tracking_number
                  FROM delivery_methods d
                  LEFT JOIN orders o ON d.order_id = o.order_id
                  WHERE d.order_id = ?";
        $query = str_replace('o.tracking_number', $orderTrackingColumn, $query);

        return $this->db->fetchOne($query, [$order_id]);
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

    private function getPaymentStatusSafe($order_id) {
        if (
            !in_array('payments', $this->getTablesSafe(), true) ||
            !$this->hasColumn('payments', 'order_id') ||
            !$this->hasColumn('payments', 'status')
        ) {
            return 'pending';
        }
        try {
            $payment = $this->db->fetchOne(
                "SELECT status FROM payments WHERE order_id = ? ORDER BY payment_id DESC LIMIT 1",
                [$order_id]
            );
            return !empty($payment['status']) ? $payment['status'] : 'pending';
        } catch (Exception $e) {
            return 'pending';
        }
    }

    private function getPaymentMethodSafe($order_id) {
        if (
            !in_array('payments', $this->getTablesSafe(), true) ||
            !$this->hasColumn('payments', 'order_id')
        ) {
            return null;
        }
        try {
            if ($this->hasColumn('payments', 'payment_method')) {
                $payment = $this->db->fetchOne(
                    "SELECT payment_method FROM payments WHERE order_id = ? ORDER BY payment_id DESC LIMIT 1",
                    [$order_id]
                );
                return $payment['payment_method'] ?? null;
            }
            if ($this->hasColumn('payments', 'method')) {
                $payment = $this->db->fetchOne(
                    "SELECT method FROM payments WHERE order_id = ? ORDER BY payment_id DESC LIMIT 1",
                    [$order_id]
                );
                return $payment['method'] ?? null;
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function getDeliveryAddressSafe($order_id) {
        if (
            !in_array('delivery_methods', $this->getTablesSafe(), true) ||
            !$this->hasColumn('delivery_methods', 'order_id') ||
            !$this->hasColumn('delivery_methods', 'address')
        ) {
            return null;
        }
        try {
            $delivery = $this->db->fetchOne(
                "SELECT address FROM delivery_methods WHERE order_id = ? ORDER BY delivery_id DESC LIMIT 1",
                [$order_id]
            );
            return $delivery['address'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Create a new order
     * @param int $user_id User ID placing the order
     * @param array $items Array of items (product_id, quantity, price)
     * @param string $payment_method Payment method
     * @param string $delivery_method Delivery method
     * @param string $delivery_address Delivery address
     * @return int|false Order ID on success, false on failure
     */
    public function createOrder($user_id, $items, $payment_method, $delivery_method, $delivery_address) {
        $this->db->beginTransaction();
        
        try {
            // Calculate total amount
            $total = 0;
            foreach ($items as $item) {
                $total += $item['quantity'] * $item['price'];
            }
            
            // Generate order number
            $order_number = 'ORD' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Insert order
            $order_id = $this->db->insert('orders', [
                'user_id' => $user_id,
                'order_number' => $order_number,
                'total_amount' => $total,
                'status' => 'pending',
                'payment_method' => $payment_method,
                'shipping_address' => $delivery_address
            ]);
            
            // Insert order items
            foreach ($items as $item) {
                $total_price = $item['quantity'] * $item['price'];
                $this->db->insert('order_items', [
                    'order_id' => $order_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $total_price
                ]);
            }
            
            // Insert payment info
            $this->db->insert('payments', [
                'order_id' => $order_id,
                'amount' => $total,
                'payment_method' => $payment_method,
                'status' => 'pending'
            ]);
            
            // Insert delivery info
            $estimated_delivery = date('Y-m-d H:i:s', strtotime('+' . ($delivery_method == 'Express' ? '2' : '5') . ' days'));
            $this->db->insert('delivery_methods', [
                'order_id' => $order_id,
                'method' => $delivery_method,
                'address' => $delivery_address,
                'estimated_delivery' => $estimated_delivery,
                'status' => 'preparing'
            ]);
            
            $this->db->commit();
            return $order_id;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Order creation failed: " . $e->getMessage());
            return false;
        }
    }
}
