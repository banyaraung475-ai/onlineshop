<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin_header.php';

// Only allow admin users
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = new Database();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_status' && isset($_POST['order_id'], $_POST['status'])) {
        $order_id = (int)$_POST['order_id'];
        $status = $_POST['status'];
        
        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (in_array($status, $valid_statuses)) {
            try {
                $order_info = $db->fetchOne("
                    SELECT o.user_id, o.order_number, o.status, u.full_name, u.email
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.user_id
                    WHERE o.order_id = ?
                ", [$order_id]);

                if (!$order_info) {
                    throw new Exception('Order not found.');
                }

                $previous_status = $order_info['status'];
                $db->update('orders', ['status' => $status], 'order_id = ?', [$order_id]);
                $message = "Order status updated successfully.";

                // Notify customer when admin sets processing/shipped/delivered.
                if ($previous_status !== $status && in_array($status, ['processing', 'shipped', 'delivered'], true)) {
                    $status_labels = [
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered'
                    ];

                    $order_number = $order_info['order_number'] ?: ('#' . $order_id);
                    $notification_title = "Order {$order_number} update";
                    $notification_message = "Your order {$order_number} status is now {$status_labels[$status]}.";

                    $db->insert('notifications', [
                        'user_id' => (int)$order_info['user_id'],
                        'title' => $notification_title,
                        'message' => $notification_message,
                        'type' => $status === 'delivered' ? 'success' : 'info'
                    ]);
                }

                // If the status is 'delivered', send an email notification
                if ($status === 'delivered') {
                    if ($order_info && !empty($order_info['email'])) {
                        $to = $order_info['email'];
                        $customer_name = $order_info['full_name'] ?? 'Valued Customer';
                        $order_number = $order_info['order_number'] ?? $order_id;
                        $subject = "Your Order #" . $order_number . " Has Been Delivered!";
                        
                        $email_body = "
                            <html>
                            <head>
                                <title>Your Order Has Been Delivered</title>
                            </head>
                            <body>
                                <p>Dear " . htmlspecialchars($customer_name) . ",</p>
                                <p>Great news! Your order with reference number <strong>#" . htmlspecialchars($order_number) . "</strong> has been successfully delivered.</p>
                                <p>We hope you enjoy your purchase. If you have any questions or feedback, please don't hesitate to contact our support team.</p>
                                <p>Thank you for shopping with us!</p>
                                <p>Best regards,<br>The " . SITE_NAME . " Team</p>
                            </body>
                            </html>
                        ";

                        $headers = "MIME-Version: 1.0" . "\r\n";
                        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                        $headers .= 'From: ' . SUPPORT_EMAIL . "\r\n";

                        // Use mail() function to send the email
                        if (!mail($to, $subject, $email_body, $headers)) {
                            // If mail fails, append a warning to the success message
                            $message .= " (Warning: Could not send delivery notification email.)";
                        }
                    }
                }
            } catch (Exception $e) {
                $error = "Failed to update order status: " . $e->getMessage();
            }
        } else {
            $error = "Invalid status provided.";
        }
    }
}

// Fetch order statistics
try {
    $pending_orders = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'] ?? 0;
    $processing_orders = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'processing'")['count'] ?? 0;
    $completed_orders = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE status IN ('shipped', 'delivered')")['count'] ?? 0;
    $cancelled_orders = $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'")['count'] ?? 0;
} catch (Exception $e) {
    $pending_orders = $processing_orders = $completed_orders = $cancelled_orders = 0;
    $error = "Failed to load order statistics: " . $e->getMessage();
}

// Fetch recent orders with user info
try {
    $orders = $db->fetchAll("
        SELECT 
            o.*,
            u.full_name,
            u.username,
            u.email,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        ORDER BY o.created_at DESC 
        LIMIT 50
    ");
} catch (Exception $e) {
    $orders = [];
    $error = "Failed to load orders: " . $e->getMessage();
}
?>

<div class="admin-sidebar">
    <div class="p-4">
        <h4 class="text-white mb-4"><?php echo SITE_NAME; ?> Admin</h4>
        <nav class="admin-nav">
            <a href="admin.php" class="admin-nav-link">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
            <a href="admin_products.php" class="admin-nav-link">
                <i class="bi bi-box me-2"></i> Products
            </a>
            <a href="admin_orders.php" class="admin-nav-link active">
                <i class="bi bi-cart3 me-2"></i> Orders
            </a>
            <a href="admin_users.php" class="admin-nav-link">
                <i class="bi bi-people me-2"></i> Users
            </a>
            <a href="admin_categories.php" class="admin-nav-link">
                <i class="bi bi-tags me-2"></i> Categories
            </a>
            <a href="admin_reviews.php" class="admin-nav-link">
                <i class="bi bi-chat-left-text me-2"></i> Reviews
            </a>
        </nav>
    </div>
</div>

<div class="admin-container">
    <div class="admin-sidebar">
        <div class="p-4">
            <h4 class="text-white mb-4"><?php echo SITE_NAME; ?> Admin</h4>
            <nav class="admin-nav">
                <a href="admin.php" class="admin-nav-link">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                <a href="admin_products.php" class="admin-nav-link">
                    <i class="bi bi-box me-2"></i> Products
                </a>
                <a href="admin_orders.php" class="admin-nav-link active">
                    <i class="bi bi-cart3 me-2"></i> Orders
                </a>
                <a href="admin_users.php" class="admin-nav-link">
                    <i class="bi bi-people me-2"></i> Users
                </a>
                <a href="admin_categories.php" class="admin-nav-link">
                    <i class="bi bi-tags me-2"></i> Categories
                </a>
                <a href="admin_reviews.php" class="admin-nav-link">
                    <i class="bi bi-chat-left-text me-2"></i> Reviews
                </a>
            </nav>
        </div>
    </div>

    <div class="admin-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Manage Orders</h1>
            <div>
                <button class="btn btn-outline-primary" id="export-orders">
                    <i class="bi bi-download me-2"></i>Export Orders
                </button>
            </div>
        </div>

    <?php if (isset($message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Order Stats -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="bi bi-clock-history text-info" style="font-size: 2rem;"></i>
                    </div>
                    <div class="stat-number display-6 fw-bold text-info"><?php echo $pending_orders; ?></div>
                    <div class="stat-label text-muted">Pending Orders</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="bi bi-gear text-warning" style="font-size: 2rem;"></i>
                    </div>
                    <div class="stat-number display-6 fw-bold text-warning"><?php echo $processing_orders; ?></div>
                    <div class="stat-label text-muted">Processing</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                    </div>
                    <div class="stat-number display-6 fw-bold text-success"><?php echo $completed_orders; ?></div>
                    <div class="stat-label text-muted">Completed</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                    </div>
                    <div class="stat-number display-6 fw-bold text-danger"><?php echo $cancelled_orders; ?></div>
                    <div class="stat-label text-muted">Cancelled</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4" id="orders-filters">
        <div class="card-header bg-light border-0">
            <h6 class="card-title mb-0">
                <i class="bi bi-funnel me-2"></i>Filter Orders
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="order-search" placeholder="Search by order ID or customer...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select">
                        <option selected>All Status</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" placeholder="Select date">
                </div>
                <div class="col-md-3">
                    <div class="d-grid">
                        <button class="btn btn-primary">
                            <i class="bi bi-filter me-2"></i>Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Recent Orders</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-cart-x display-4 mb-3"></i>
                                    <h5>No Orders Found</h5>
                                    <p>When customers place orders, they will appear here.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <?php
                                $orderStatus = (string)($order['status'] ?? 'pending');
                                $paymentStatus = (string)($order['payment_status'] ?? 'pending');
                            ?>
                            <td><strong>#<?php echo htmlspecialchars($order['order_number'] ?? $order['order_id']); ?></strong></td>
                            <td>
                                <div>
                                    <div class="fw-bold"><?php 
                                        $customer_name = !empty($order['full_name']) ? $order['full_name'] : 'Guest Customer';
                                        echo htmlspecialchars($customer_name); 
                                    ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars(!empty($order['email']) ? $order['email'] : ''); ?></small>
                                </div>
                            </td>
                            <td><?php echo $order['created_at'] ? date('M d, Y', strtotime($order['created_at'])) : 'N/A'; ?></td>
                            <td><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?></td>
                            <td><strong>$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></strong></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($orderStatus) {
                                        'pending' => 'info',
                                        'processing' => 'warning',
                                        'shipped' => 'primary',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($orderStatus); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($paymentStatus) {
                                        'paid' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        'refunded' => 'secondary',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($paymentStatus); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary view-order-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewOrderModal"
                                            data-order-id="<?php echo $order['order_id']; ?>">
                                        View
                                    </button>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Update
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item update-status" href="#" data-order-id="<?php echo $order['order_id']; ?>" data-status="processing">Processing</a></li>
                                            <li><a class="dropdown-item update-status" href="#" data-order-id="<?php echo $order['order_id']; ?>" data-status="shipped">Shipped</a></li>
                                            <li><a class="dropdown-item update-status" href="#" data-order-id="<?php echo $order['order_id']; ?>" data-status="delivered">Delivered</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item update-status text-danger" href="#" data-order-id="<?php echo $order['order_id']; ?>" data-status="cancelled">Cancel Order</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewOrderModalLabel">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="orderDetails">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>

<style>
/* Enhanced Data Display Styles */
.stat-card {
    background: white;
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.table th {
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
    font-weight: 600;
    color: #495057;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

.badge {
    font-weight: 500;
    padding: 0.375rem 0.625rem;
}

.btn-group .btn {
    margin-right: 0.25rem;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

/* Modal Enhancements */
.modal-content {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.modal-header {
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 0.75rem 0.75rem 0 0;
}

.modal-title {
    font-weight: 600;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .btn-group .btn {
        width: 100%;
    }
}

/* Loading states */
.spinner-border {
    width: 2rem;
    height: 2rem;
}

/* Empty state styling */
.text-muted .bi-cart-x {
    opacity: 0.5;
}
</style>
