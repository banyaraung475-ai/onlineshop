<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'classes/Order.php';
require_once 'classes/User.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user = new User($db);
$userDetails = $user->getUserById($_SESSION['user_id']);

// Initialize messages
$success = '';
$error = '';

$order = new Order($db);
$orders = $order->getOrdersByUser($_SESSION['user_id']);

$page_title = "My Orders";
include 'includes/header.php';
?>
<div class="container py-5">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="position-relative mb-3">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($userDetails['full_name'] ?? $userDetails['username']); ?>&background=random&size=150" 
                             class="rounded-circle border" 
                             width="150" 
                             height="150"
                             alt="Profile">
                        <div class="position-absolute bottom-0 end-0 bg-primary rounded-circle p-2 border border-3 border-white">
                            <i class="bi bi-pencil-fill text-white"></i>
                        </div>
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($userDetails['full_name'] ?? $userDetails['username']); ?></h5>
                    <small class="text-muted">@<?php echo htmlspecialchars($userDetails['username']); ?></small>
                    
                    <div class="list-group list-group-flush mt-4">
                        <a href="my-account.php" class="list-group-item list-group-item-action ">
                            <i class="bi bi-person-fill me-2"></i> My Profile
                        </a>
                        <a href="my-orders.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-bag-check me-2"></i> My Orders
                        </a>
                        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <h2>My Orders</h2>
            
            <?php if (count($orders) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Delivery</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['order_id']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                <td><?php echo $order['items_total']; ?></td>
                                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        switch($order['status']) {
                                            case 'processing': echo 'primary'; break;
                                            case 'shipped': echo 'info'; break;
                                            case 'delivered': echo 'success'; break;
                                            case 'cancelled': echo 'danger'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        switch($order['status']) {
                                            case 'shipped': echo 'info'; break;
                                            case 'in_transit': echo 'warning'; break;
                                            case 'delivered': echo 'success'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="order-detail.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    You haven't placed any orders yet. <a href="products.php">Start shopping</a> to place your first order.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script>
// Add some basic client-side enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to table rows
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('click', function() {
            const viewBtn = this.querySelector('.btn-primary');
            if (viewBtn) {
                window.location.href = viewBtn.href;
            }
        });
        
        row.style.cursor = 'pointer';
    });
});
</script>
