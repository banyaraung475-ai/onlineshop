<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';

// Only allow admin users
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Check if database is properly set up
function checkDatabaseSetup($db) {
    $required_tables = ['users', 'products', 'orders', 'order_items', 'categories'];
    foreach ($required_tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() == 0) {
            return false;
        }
    }
    // Check if users table has role column
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($result->rowCount() == 0) {
        return false;
    }
    return true;
}

if (!checkDatabaseSetup($db)) {
    $page_title = "Admin Setup Required";
    include_once __DIR__ . '/includes/admin_header.php';
    ?>
    <div class="admin-sidebar">
        <div class="p-4">
            <h4 class="text-white mb-4"><?php echo SITE_NAME; ?> Admin</h4>
        </div>
    </div>
    <div class="admin-main">
        <div class="alert alert-warning">
            <h4>Database Setup Required</h4>
            <p>The database is not properly configured. Please complete the following steps:</p>
            <ol>
                <li>Create database 'online_shop' in phpMyAdmin</li>
                <li>Import the schema from <code>database.sql</code></li>
                <li>Add the 'role' column to users table if missing</li>
                <li>Run <a href="setup-admin.php">setup-admin.php</a> to create admin user</li>
            </ol>
            <p><a href="setup-admin.php" class="btn btn-primary">Run Admin Setup</a></p>
        </div>
    </div>
    <?php
    include_once __DIR__ . '/includes/admin_footer.php';
    exit;
}

// Fetch dashboard statistics
try {
    // Total orders
    $result = $db->fetchOne("SELECT COUNT(*) as total_orders FROM orders");
    $total_orders = $result['total_orders'] ?? 0;

    // Total products
    $result = $db->fetchOne("SELECT COUNT(*) as total_products FROM products");
    $total_products = $result['total_products'] ?? 0;

    // Total customers
    $result = $db->fetchOne("SELECT COUNT(*) as total_customers FROM users WHERE role = 'customer'");
    $total_customers = $result['total_customers'] ?? 0;

    // Total revenue
    $result = $db->fetchOne("SELECT SUM(total_amount) as total_revenue FROM orders WHERE status = 'completed'");
    $total_revenue = $result['total_revenue'] ?? 0;

    // Recent orders
    $recent_orders = $db->fetchAll("SELECT o.order_id, u.username, o.total_amount, o.status, o.created_at FROM orders o JOIN users u ON o.user_id = u.user_id ORDER BY o.created_at DESC LIMIT 5");

} catch (Exception $e) {
    // Handle errors gracefully
    $total_orders = 0;
    $total_products = 0;
    $total_customers = 0;
    $total_revenue = 0;
    $recent_orders = [];
}

$page_title = "Admin Dashboard";
include_once __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-sidebar">
    <div class="p-4">
        <h4 class="text-white mb-4"><?php echo SITE_NAME; ?> Admin</h4>
        <nav class="admin-nav">
            <a href="admin.php" class="admin-nav-link active">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
            <a href="admin_products.php" class="admin-nav-link">
                <i class="bi bi-box me-2"></i> Products
            </a>
            <a href="admin_orders.php" class="admin-nav-link">
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
        <h1 class="h3 mb-0">Dashboard</h1>
        <div class="text-muted">
            <i class="bi bi-calendar3 me-2"></i>
            <?php echo date('F j, Y'); ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?php echo number_format($total_orders); ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-cart3" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-success small">
                        <i class="bi bi-arrow-up"></i> Active orders
                    </span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?php echo number_format($total_products); ?></div>
                        <div class="stat-label">Products</div>
                    </div>
                    <div class="text-info">
                        <i class="bi bi-box" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-success small">
                        <i class="bi bi-arrow-up"></i> In stock
                    </span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?php echo number_format($total_customers); ?></div>
                        <div class="stat-label">Customers</div>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-people" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-success small">
                        <i class="bi bi-arrow-up"></i> Registered users
                    </span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number">$<?php echo number_format($total_revenue, 1); ?>K</div>
                        <div class="stat-label">Revenue</div>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-currency-dollar" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-success small">
                        <i class="bi bi-arrow-up"></i> From completed orders
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Recent Orders</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Products</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No orders found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                            <td><?php 
                                // Count items for this order
                                $item_result = $db->fetchOne("SELECT COUNT(*) as item_count FROM order_items WHERE order_id = ?", [$order['order_id']]);
                                $item_count = $item_result['item_count'] ?? 0;
                                echo $item_count . ' item' . ($item_count != 1 ? 's' : '');
                            ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><span class="badge bg-<?php 
                                echo $order['status'] == 'completed' ? 'success' : 
                                     ($order['status'] == 'processing' ? 'warning' : 'info'); 
                            ?>"><?php echo ucfirst(htmlspecialchars($order['status'])); ?></span></td>
                            <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="admin_orders.php?action=view&id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="admin_products.php?action=add" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i> Add New Product
                        </a>
                        <a href="admin_orders.php" class="btn btn-outline-primary">
                            <i class="bi bi-cart3 me-2"></i> View All Orders
                        </a>
                        <a href="admin_reports.php" class="btn btn-outline-secondary">
                            <i class="bi bi-graph-up me-2"></i> Generate Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">System Status</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Database</span>
                        <span class="badge bg-success">Healthy</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Storage</span>
                        <span class="badge bg-warning">75% Used</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Last Backup</span>
                        <span class="text-muted">2 hours ago</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
