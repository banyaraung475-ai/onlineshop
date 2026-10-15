<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = "Track Order";
include_once __DIR__ . '/includes/header.php';

// Get order from URL parameter
$orderNumber = isset($_GET['order']) ? $_GET['order'] : '';
$trackingInfo = null;

if ($orderNumber) {
    // Get order and delivery information
    $db = new Database();
    
    $order = $db->fetchOne("
        SELECT o.*, u.full_name, u.email, u.phone,
               d.method as delivery_method, d.tracking_number, 
               d.status as delivery_status, d.estimated_delivery, d.actual_delivery,
               d.address as delivery_address
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        LEFT JOIN delivery_methods d ON o.order_id = d.order_id
        WHERE o.order_id = ? OR o.order_number = ?
    ", [$orderNumber, $orderNumber]);
    
    if ($order) {
        $orderItems = $db->fetchAll("
            SELECT oi.*, p.name as product_name, p.image_url
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ", [$order['order_id']]);
        
        $payment = $db->fetchOne("
            SELECT * FROM payments WHERE order_id = ?
        ", [$order['order_id']]);
        
        $trackingInfo = [
            'order' => $order,
            'items' => $orderItems,
            'payment' => $payment
        ];
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h3 class="mb-4">Track Your Order</h3>
                    
                    <!-- Order Search Form -->
                    <div class="mb-4">
                        <form method="GET" class="d-flex gap-2">
                            <input type="text" class="form-control" name="order" 
                                   placeholder="Enter order number or ID" 
                                   value="<?php echo htmlspecialchars($orderNumber); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>Track
                            </button>
                        </form>
                    </div>
                    
                    <?php if ($trackingInfo): ?>
                        <!-- Order Details -->
                        <div class="border rounded p-4 mb-4">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h5>Order #<?php echo $order['order_id']; ?></h5>
                                    <p class="text-muted">Placed on <?php echo date('M j, Y', strtotime($order['order_date'])); ?></p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <span class="badge bg-<?php echo getStatusBadgeClass($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Delivery Address</h6>
                                    <p class="text-muted"><?php echo htmlspecialchars($order['delivery_address'] ?? 'Not specified'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Payment Method</h6>
                                    <p class="text-muted">
                                        <?php echo htmlspecialchars($payment['payment_method'] ?? 'Not specified'); ?>
                                        <?php if ($payment['transaction_id']): ?>
                                            <br><small>Transaction: <?php echo $payment['transaction_id']; ?></small>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tracking Timeline -->
                        <div class="mb-4">
                            <h5 class="mb-3">Delivery Status</h5>
                            <div class="timeline">
                                <?php
                                $timelineSteps = getDeliveryTimeline($order['delivery_status'], $order['order_date'], $order['estimated_delivery'], $order['actual_delivery']);
                                foreach ($timelineSteps as $step):
                                ?>
                                <div class="timeline-item <?php echo $step['completed'] ? 'completed' : ''; ?>">
                                    <div class="timeline-marker">
                                        <i class="bi <?php echo $step['icon']; ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6><?php echo $step['title']; ?></h6>
                                        <p class="text-muted small"><?php echo $step['description']; ?></p>
                                        <?php if ($step['date']): ?>
                                            <small class="text-muted"><?php echo $step['date']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Order Items -->
                        <div class="mb-4">
                            <h5 class="mb-3">Order Items</h5>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orderItems as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://via.placeholder.com/50'); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                         class="rounded me-3" style="width: 50px; height: 50px;">
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><?php echo number_format($item['price']); ?> Ks</td>
                                            <td><strong><?php echo number_format($item['quantity'] * $item['price']); ?> Ks</strong></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3">Total Amount</th>
                                            <th><strong><?php echo number_format($order['total_amount']); ?> Ks</strong></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Tracking Number -->
                        <?php if ($order['tracking_number']): ?>
                        <div class="alert alert-info">
                            <h6><i class="bi bi-truck me-2"></i>Tracking Number</h6>
                            <p class="mb-2">
                                <strong><?php echo $order['tracking_number']; ?></strong>
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyTrackingNumber('<?php echo $order['tracking_number']; ?>')">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </p>
                            <small class="text-muted">Use this number to track your package with the delivery service</small>
                        </div>
                        <?php endif; ?>
                        
                    <?php elseif ($orderNumber): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Order not found. Please check your order number and try again.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Quick Links -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Quick Links</h5>
                    <div class="d-grid gap-2">
                        <a href="my-orders.php" class="btn btn-outline-primary">
                            <i class="bi bi-box-seam me-2"></i>My Orders
                        </a>
                        <a href="contact.php" class="btn btn-outline-primary">
                            <i class="bi bi-headset me-2"></i>Contact Support
                        </a>
                        <a href="products.php" class="btn btn-outline-primary">
                            <i class="bi bi-bag me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Delivery Information -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Delivery Information</h5>
                    <div class="mb-3">
                        <h6>Standard Delivery</h6>
                        <p class="text-muted small">3-5 business days</p>
                    </div>
                    <div class="mb-3">
                        <h6>Express Delivery</h6>
                        <p class="text-muted small">1-2 business days</p>
                    </div>
                    <div class="mb-3">
                        <h6>Store Pickup</h6>
                        <p class="text-muted small">Available next day</p>
                    </div>
                    <hr>
                    <div class="text-center">
                        <small class="text-muted">
                            <i class="bi bi-telephone me-1"></i>
                            For delivery issues: +95 1 234 5678
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #f3f4f6;
    border: 2px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #6b7280;
}

.timeline-item.completed .timeline-marker {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

.timeline-content h6 {
    margin-bottom: 5px;
    font-weight: 600;
}

.timeline-content p {
    margin-bottom: 0;
}
</style>

<script>
function copyTrackingNumber(trackingNumber) {
    navigator.clipboard.writeText(trackingNumber).then(function() {
        alert('Tracking number copied to clipboard!');
    });
}
</script>

<?php
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'processing': return 'info';
        case 'shipped': return 'primary';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getDeliveryTimeline($status, $orderDate, $estimatedDelivery, $actualDelivery) {
    $timeline = [
        [
            'title' => 'Order Placed',
            'description' => 'Your order has been received',
            'icon' => 'bi-check-circle',
            'completed' => true,
            'date' => date('M j, Y g:i A', strtotime($orderDate))
        ],
        [
            'title' => 'Order Confirmed',
            'description' => 'Your order has been confirmed and is being prepared',
            'icon' => 'bi-check-circle',
            'completed' => in_array($status, ['processing', 'shipped', 'delivered']),
            'date' => in_array($status, ['processing', 'shipped', 'delivered']) ? date('M j, Y g:i A', strtotime($orderDate . ' +1 hour')) : null
        ],
        [
            'title' => 'Preparing for Shipment',
            'description' => 'Your order is being packed and prepared for delivery',
            'icon' => 'bi-box-seam',
            'completed' => in_array($status, ['shipped', 'delivered']),
            'date' => in_array($status, ['shipped', 'delivered']) ? date('M j, Y g:i A', strtotime($orderDate . ' +2 hours')) : null
        ],
        [
            'title' => 'Shipped',
            'description' => 'Your order has been shipped and is on its way',
            'icon' => 'bi-truck',
            'completed' => $status === 'shipped' || $status === 'delivered',
            'date' => ($status === 'shipped' || $status === 'delivered') ? date('M j, Y g:i A', strtotime($orderDate . ' +1 day')) : null
        ],
        [
            'title' => 'Delivered',
            'description' => 'Your order has been delivered successfully',
            'icon' => 'bi-house-check',
            'completed' => $status === 'delivered',
            'date' => $status === 'delivered' ? ($actualDelivery ? date('M j, Y g:i A', strtotime($actualDelivery)) : 'Today') : null
        ]
    ];
    
    return $timeline;
}
?>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
