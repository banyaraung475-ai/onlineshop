<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'classes/Cart.php';
require_once 'classes/User.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = 'checkout.php';
    header("Location: login.php");
    exit();
}

$db = new Database();
$cart = new Cart($db);
$user = new User($db);
$current_user = $user->getUserById($_SESSION['user_id']);

// Initialize variables
$payment_method = '';
$delivery_method = '';
$delivery_address = $current_user['address'] ?? '';
$notes = '';
$errors = [];
$shipping_fee = 2000; // Default to in-city delivery
$deliveryFees = [
    'InCityStandard' => 2000,
    'InCityExpress' => 5000,
    'FarAwayStandard' => 4000,
    'FarAwayExpress' => 7000
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $delivery_method = filter_input(INPUT_POST, 'delivery_method', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $delivery_address = filter_input(INPUT_POST, 'delivery_address', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (!isset($deliveryFees[$delivery_method])) {
        $delivery_method = '';
    }

    // Validate inputs
    if (empty($payment_method)) {
        $errors[] = "Please select a payment method";
    }
    if (empty($delivery_method)) {
        $errors[] = "Please select a delivery method";
    }
    if (empty($delivery_address)) {
        $errors[] = "Please enter a delivery address";
    }
    if ($cart->getItemCount() === 0) {
        $errors[] = "Your cart is empty";
    }
    
    if (empty($errors)) {
        $result = $cart->checkout(
            $_SESSION['user_id'],
            $payment_method,
            $delivery_method,
            $delivery_address,
            $notes
        );
        
        if (isset($result['success']) && $result['success']) {
            $_SESSION['new_order'] = $result['order_id'];
            header("Location: order-confirmation.php?order_id=" . $result['order_id']);
            exit();
        } else {
            $errors[] = $result['message'] ?? 'There was an unknown error processing your order. Please try again or contact support.';
        }
    }
}

$cartItems = $cart->getItems();
$subtotal = $cart->getTotal();

// Calculate shipping fee based on selected method (if form was submitted)
if (isset($_POST['delivery_method'])) {
    $selectedMethod = $_POST['delivery_method'];
    $shipping_fee = $deliveryFees[$selectedMethod] ?? 2000;
} else {
    $shipping_fee = 2000; // Default to in-city delivery
}

$total = $subtotal + $shipping_fee;

$page_title = "Checkout";
include 'includes/header.php';
?>

<div class="container py-4">
    <form method="POST" id="checkout-form">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-truck me-2"></i>Delivery Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" 
                                       value="<?php echo htmlspecialchars($current_user['full_name']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" 
                                       value="<?php echo htmlspecialchars($current_user['phone']); ?>" readonly>
                            </div>
                            <div class="col-12">
                                <label for="delivery_address" class="form-label">Delivery Address</label>
                                <textarea class="form-control" id="delivery_address" name="delivery_address" 
                                          rows="3" required><?php echo htmlspecialchars($delivery_address); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="delivery_method" class="form-label">Delivery Method</label>
                                <select class="form-select" id="delivery_method" name="delivery_method" required>
                                    <option value="">Select method</option>
                                    <optgroup label="In City">
                                        <option value="InCityStandard" <?php echo ($delivery_method === 'InCityStandard' || $delivery_method === 'InCity') ? 'selected' : ''; ?>>In City Standard (1-2 days) - 2,000 Ks</option>
                                        <option value="InCityExpress" <?php echo ($delivery_method === 'InCityExpress' || $delivery_method === 'InCity') ? 'selected' : ''; ?>>In City Express (3-5 days) - 5,000 Ks</option>
                                    </optgroup>
                                       <optgroup label="Far Away">
                                        <option value="FarAwayStandard" <?php echo ($delivery_method === 'FarAwayStandard' || $delivery_method === 'FarAway') ? 'selected' : ''; ?>>Far Away Standard (3-4 days) - 4,000 Ks</option>
                                        <option value="FarAwayExpress" <?php echo ($delivery_method === 'FarAwayExpress' || $delivery_method === 'FarAway') ? 'selected' : ''; ?>>Far Away Express (6-7 days) - 7,000 Ks</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">Order Notes (Optional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo htmlspecialchars($notes); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-credit-card me-2"></i>Payment Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="kpay" value="KPay" required
                                       <?php echo ($payment_method === 'KPay') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="kpay">
                                    <img src="assets/images/kpaylogo.png" alt="KPay" height="24" class="ms-2">
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="ayapay" value="AyaPay"
                                       <?php echo ($payment_method === 'AyaPay') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ayapay">
                                    <img src="assets/images/Ayalogo.png" alt="AyaPay" height="24" class="ms-2">
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="cod" value="CashOnDelivery"
                                       <?php echo ($payment_method === 'CashOnDelivery') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cod">
                                    Cash on Delivery
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-cart-check me-2"></i>Order Summary</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="list-group list-group-flush mb-3">
                        <?php foreach ($cartItems as $product_id => $item): 
                            if (!is_array($item) || !isset($item['name'], $item['quantity'], $item['price'])) continue;
                        ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <small class="text-muted">Qty: <?php echo htmlspecialchars($item['quantity']); ?></small>
                                </div>
                                <span><?php echo number_format($item['price'] * $item['quantity']); ?> Ks</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span><?php echo number_format($subtotal); ?> Ks</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Delivery Fee:</span>
                        <span id="shipping-fee"><?php echo number_format($shipping_fee); ?> Ks</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total:</span>
                        <span id="total-amount"><?php echo number_format($total); ?> Ks</span>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" name="place_order" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle me-2"></i> Place Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deliveryMethodSelect = document.getElementById('delivery_method');
    const shippingFeeEl = document.getElementById('shipping-fee');
    const totalAmountEl = document.getElementById('total-amount');
    const subtotal = <?php echo $subtotal; ?>;
    const deliveryFees = {
        InCityStandard: 2000,
        InCityExpress: 5000,
        FarAwayStandard: 4000,
        FarAwayExpress: 7000
    };

    deliveryMethodSelect.addEventListener('change', function() {
        const method = this.value;
        const shippingFee = deliveryFees[method] || 2000;
        const total = subtotal + shippingFee;

        if(shippingFeeEl) {
            shippingFeeEl.textContent = shippingFee.toLocaleString() + ' Ks';
        }
        if(totalAmountEl) {
            totalAmountEl.textContent = total.toLocaleString() + ' Ks';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
