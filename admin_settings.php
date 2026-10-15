<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Only allow admin users
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$page_title = "System Settings";
include_once __DIR__ . '/includes/admin_header.php';
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
            <a href="admin_reports.php" class="admin-nav-link">
                <i class="bi bi-graph-up me-2"></i> Reports
            </a>
            <a href="admin_settings.php" class="admin-nav-link active">
                <i class="bi bi-gear me-2"></i> Settings
            </a>
        </nav>
    </div>
</div>

<div class="admin-main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">System Settings</h1>
        <div>
            <button class="btn btn-success">
                <i class="bi bi-check-circle me-2"></i>Save All Settings
            </button>
        </div>
    </div>

    <!-- General Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">General Settings</h5>
        </div>
        <div class="card-body">
            <form>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Store Name</label>
                        <input type="text" class="form-control" value="<?php echo SITE_NAME; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Store Email</label>
                        <input type="email" class="form-control" value="admin@example.com">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Store Phone</label>
                        <input type="tel" class="form-control" value="+1 234 567 8900">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Currency</label>
                        <select class="form-select">
                            <option selected>USD ($)</option>
                            <option>EUR (€)</option>
                            <option>GBP (£)</option>
                            <option>JPY (¥)</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Store Description</label>
                    <textarea class="form-control" rows="3">Your one-stop shop for all your needs. Quality products at unbeatable prices.</textarea>
                </div>
            </form>
        </div>
    </div>

    <!-- Payment Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Payment Methods</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="paypal" checked>
                        <label class="form-check-label" for="paypal">
                            <i class="bi bi-paypal me-2"></i>PayPal
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="stripe" checked>
                        <label class="form-check-label" for="stripe">
                            <i class="bi bi-credit-card me-2"></i>Stripe
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="cod" checked>
                        <label class="form-check-label" for="cod">
                            <i class="bi bi-cash me-2"></i>Cash on Delivery
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="kpay" checked>
                        <label class="form-check-label" for="kpay">
                            <i class="bi bi-phone me-2"></i>KPay
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="ayapay">
                        <label class="form-check-label" for="ayapay">
                            <i class="bi bi-phone me-2"></i>AyaPay
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="bank">
                        <label class="form-check-label" for="bank">
                            <i class="bi bi-bank me-2"></i>Bank Transfer
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Shipping Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Shipping Settings</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Free Shipping Threshold</label>
                    <input type="number" class="form-control" value="50" step="0.01">
                    <small class="text-muted">Orders above this amount get free shipping</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Standard Shipping Fee</label>
                    <input type="number" class="form-control" value="5.99" step="0.01">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Express Shipping Fee</label>
                    <input type="number" class="form-control" value="12.99" step="0.01">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Processing Time (days)</label>
                    <input type="number" class="form-control" value="2">
                </div>
            </div>
        </div>
    </div>

    <!-- Email Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Email Notifications</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="orderConfirm" checked>
                        <label class="form-check-label" for="orderConfirm">
                            Order Confirmation
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="shippingNotify" checked>
                        <label class="form-check-label" for="shippingNotify">
                            Shipping Notifications
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="newsletter">
                        <label class="form-check-label" for="newsletter">
                            Newsletter Subscriptions
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="passwordReset" checked>
                        <label class="form-check-label" for="passwordReset">
                            Password Reset
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="accountChanges" checked>
                        <label class="form-check-label" for="accountChanges">
                            Account Changes
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="promotional">
                        <label class="form-check-label" for="promotional">
                            Promotional Emails
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Settings -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Security Settings</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="twoFactor">
                        <label class="form-check-label" for="twoFactor">
                            Two-Factor Authentication
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="sessionTimeout" checked>
                        <label class="form-check-label" for="sessionTimeout">
                            Session Timeout
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Session Duration (minutes)</label>
                        <input type="number" class="form-control" value="30">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Max Login Attempts</label>
                        <input type="number" class="form-control" value="5">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
