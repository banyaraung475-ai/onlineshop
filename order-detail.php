<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'includes/auth.php';
require_once 'classes/Order.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: my-orders.php");
    exit();
}

$order_id = $_GET['id'];
$db = new Database();
$order = new Order($db);
$orderDetails = $order->getOrderById($order_id, $_SESSION['user_id']);
$orderItems = $order->getOrderItems($order_id);

if (!$orderDetails) {
    header("Location: my-orders.php");
    exit();
}

$page_title = "Order #" . $order_id;
include 'includes/header.php';

$orderDateRaw = $orderDetails['order_date'] ?? ($orderDetails['created_at'] ?? null);
$orderDateLabel = 'N/A';
if (!empty($orderDateRaw)) {
    $ts = strtotime((string)$orderDateRaw);
    if ($ts !== false) {
        $orderDateLabel = date('F j, Y, g:i a', $ts);
    }
}

$itemsSubtotal = 0.0;
foreach (($orderItems ?? []) as $item) {
    if (isset($item['total_price'])) {
        $itemsSubtotal += (float)$item['total_price'];
    } elseif (isset($item['quantity'], $item['unit_price'])) {
        $itemsSubtotal += (float)$item['quantity'] * (float)$item['unit_price'];
    } elseif (isset($item['quantity'], $item['price'])) {
        $itemsSubtotal += (float)$item['quantity'] * (float)$item['price'];
    }
}

$shippingFee = 0.0;
if (isset($orderDetails['shipping_fee'])) {
    $shippingFee = (float)$orderDetails['shipping_fee'];
} elseif (isset($orderDetails['shipping_amount'])) {
    $shippingFee = (float)$orderDetails['shipping_amount'];
}

$totalAmount = null;
if (isset($orderDetails['total_amount'])) {
    $totalAmount = (float)$orderDetails['total_amount'];
} elseif (isset($orderDetails['total'])) {
    $totalAmount = (float)$orderDetails['total'];
}
if ($totalAmount === null) {
    $totalAmount = $itemsSubtotal + $shippingFee;
}
if ($shippingFee <= 0 && $totalAmount >= $itemsSubtotal) {
    $shippingFee = $totalAmount - $itemsSubtotal;
}

$deliveryStatus = $orderDetails['delivery_status'] ?? '';
if ($deliveryStatus === '') {
    $deliveryStatus = $orderDetails['status'] ?? 'pending';
}
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-lg-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="position-relative mb-3">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username'] ?? 'User'); ?>&background=random&size=150" 
                             class="rounded-circle border" 
                             width="150" 
                             height="150"
                             alt="Profile">
                        <div class="position-absolute bottom-0 end-0 bg-primary rounded-circle p-2 border border-3 border-white">
                            <i class="bi bi-pencil-fill text-white"></i>
                        </div>
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></h5>
                    <small class="text-muted">Member</small>
                    
                    <div class="list-group list-group-flush mt-4">
                        <a href="my-account.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-person-fill me-2"></i> My Profile
                        </a>
                        <a href="my-orders.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-bag-check me-2"></i> My Orders
                        </a>
                        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Order #<?php echo htmlspecialchars((string)($orderDetails['order_number'] ?? $order_id)); ?></h2>
                <a href="my-orders.php" class="btn btn-outline-secondary">Back to Orders</a>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Order Summary</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Order Date:</strong> <?php echo htmlspecialchars($orderDateLabel); ?></p>
                            <p><strong>Status:</strong> <span class="badge bg-<?php 
                                $status = $orderDetails['status'] ?? 'pending';
                                switch($status) {
                                    case 'processing': echo 'primary'; break;
                                    case 'shipped': echo 'info'; break;
                                    case 'delivered': echo 'success'; break;
                                    case 'cancelled': echo 'danger'; break;
                                    case 'pending': echo 'warning'; break;
                                    default: echo 'secondary';
                                }
                            ?>">
                                <?php echo ucfirst($status); ?>
                            </span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($orderDetails['payment_method'] ?? 'N/A'); ?></p>
                            <p><strong>Payment Status:</strong> <span class="badge bg-<?php 
                                $paymentStatus = $orderDetails['payment_status'] ?? 'pending';
                                echo $paymentStatus === 'completed' ? 'success' : 'warning'; 
                            ?>">
                                <?php echo ucfirst($paymentStatus); ?>
                            </span></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Order Items</h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($orderItems)): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orderItems as $item): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    // Try different possible image field names
                                                    $imageField = null;
                                                    $possibleImageFields = ['product_image', 'image_path', 'image', 'img', 'image_url', 'product_img'];
                                                    
                                                    foreach ($possibleImageFields as $field) {
                                                        if (!empty($item[$field])) {
                                                            $imageField = $item[$field];
                                                            break;
                                                        }
                                                    }
                                                    
                                                    $imagePath = $imageField ?: 'placeholder.jpg';
                                                    // Check if image path is already a full URL or just a filename
                                                    if (!filter_var($imagePath, FILTER_VALIDATE_URL) && !str_starts_with($imagePath, 'http')) {
                                                        $imagePath = "assets/images/" . $imagePath;
                                                    }
                                                    ?>
                                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" width="50" class="me-3" alt="Product Image">
                                                    <?php 
                                                    // Try different possible name fields
                                                    $nameField = null;
                                                    $possibleNameFields = ['product_name', 'name', 'title', 'product_title'];
                                                    
                                                    foreach ($possibleNameFields as $field) {
                                                        if (!empty($item[$field])) {
                                                            $nameField = $item[$field];
                                                            break;
                                                        }
                                                    }
                                                    echo htmlspecialchars($nameField ?? 'Product Name');
                                                    ?>
                                                </td>
                                                <?php
                                                    $itemPrice = 0.0;
                                                    if (isset($item['unit_price'])) {
                                                        $itemPrice = (float)$item['unit_price'];
                                                    } elseif (isset($item['price'])) {
                                                        $itemPrice = (float)$item['price'];
                                                    }
                                                    $itemQty = (int)($item['quantity'] ?? 1);
                                                    $itemTotal = isset($item['total_price'])
                                                        ? (float)$item['total_price']
                                                        : $itemPrice * $itemQty;
                                                ?>
                                                <td><?php echo formatCurrency($itemPrice); ?></td>
                                                <td><?php echo $itemQty; ?></td>
                                                <td><?php echo formatCurrency($itemTotal); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                                <td><?php echo formatCurrency($itemsSubtotal); ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>Delivery Fee:</strong></td>
                                                <td><?php echo formatCurrency($shippingFee); ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                                <td><?php echo formatCurrency($totalAmount); ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    No items found for this order.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Delivery Information</h4>
                        </div>
                        <div class="card-body">
                            <p><strong>Method:</strong> <?php echo htmlspecialchars($orderDetails['delivery_method'] ?? 'Standard'); ?></p>
                            <p><strong>Address:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($orderDetails['delivery_address'] ?? 'No address provided')); ?></p>
                            <p><strong>Status:</strong> <span class="badge bg-<?php 
                                switch($deliveryStatus) {
                                    case 'shipped': echo 'info'; break;
                                    case 'in_transit': echo 'warning'; break;
                                    case 'delivered': echo 'success'; break;
                                    case 'pending': echo 'secondary'; break;
                                    default: echo 'secondary';
                                }
                            ?>">
                                <?php echo ucfirst($deliveryStatus); ?>
                            </span></p>
                            <?php if (!empty($orderDetails['estimated_delivery']) && ($deliveryStatus === 'shipped' || $deliveryStatus === 'in_transit')): ?>
                                <p><strong>Estimated Delivery:</strong> <?php echo date('F j, Y', strtotime($orderDetails['estimated_delivery'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (($orderDetails['status'] ?? '') === 'processing'): ?>
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h4>Cancel Order</h4>
                            </div>
                            <div class="card-body">
                                <p>You can cancel this order if it hasn't been shipped yet.</p>
                                <button class="btn btn-danger btn-block">Cancel Order</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php include 'includes/footer.php'; ?>
