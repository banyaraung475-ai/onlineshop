<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'classes/Order.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: my-orders.php");
    exit();
}

$order_id = (int)$_GET['order_id'];
$user_id = $_SESSION['user_id'];

$db = new Database();
$order = new Order($db);

$orderDetails = $order->getOrderById($order_id, $user_id);

// Ensure the order belongs to the current user
if (!$orderDetails) {
    header("Location: my-orders.php");
    exit();
}

$page_title = "Order Confirmation";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg text-center">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h1 class="h3 mb-3">Thank You For Your Order!</h1>
                    <p class="text-muted">Your order has been placed successfully. A confirmation email has been sent to you.</p>
                    <p class="fw-bold">Order Number: <?php echo htmlspecialchars($orderDetails['order_number']); ?></p>
                    
                    <div class="card mt-4 text-start">
                        <div class="card-header">
                            Order Summary
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($orderDetails['items'] as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php echo htmlspecialchars($item['name']); ?>
                                        <small class="d-block text-muted">Quantity: <?php echo $item['quantity']; ?></small>
                                    </div>
                                    <span><?php echo number_format($item['total_price']); ?> Ks</span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <span><?php echo number_format($orderDetails['items_total']); ?> Ks</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Shipping:</span>
                                <span><?php echo number_format($orderDetails['shipping_fee']); ?> Ks</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total:</span>
                                <span><?php echo number_format($orderDetails['total_amount']); ?> Ks</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="products.php" class="btn btn-outline-primary">Continue Shopping</a>
                        <a href="my-orders.php" class="btn btn-primary">View My Orders</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Unset the session variable to prevent re-showing the confirmation on refresh
unset($_SESSION['new_order']);
include 'includes/footer.php';
?>
<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'classes/Order.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: my-orders.php");
    exit();
}

$order_id = (int)$_GET['order_id'];
$user_id = $_SESSION['user_id'];

$db = new Database();
$order = new Order($db);

$orderDetails = $order->getOrderById($order_id, $user_id);

// Ensure the order belongs to the current user
if (!$orderDetails) {
    header("Location: my-orders.php");
    exit();
}

$page_title = "Order Confirmation";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg text-center">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h1 class="h3 mb-3">Thank You For Your Order!</h1>
                    <p class="text-muted">Your order has been placed successfully. A confirmation email has been sent to you.</p>
                    <p class="fw-bold">Order Number: <?php echo htmlspecialchars($orderDetails['order_number']); ?></p>
                    
                    <div class="card mt-4 text-start">
                        <div class="card-header">
                            Order Summary
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($orderDetails['items'] as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php echo htmlspecialchars($item['name']); ?>
                                        <small class="d-block text-muted">Quantity: <?php echo $item['quantity']; ?></small>
                                    </div>
                                    <span><?php echo number_format($item['total_price']); ?> Ks</span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <span><?php echo number_format($orderDetails['items_total']); ?> Ks</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Shipping:</span>
                                <span><?php echo number_format($orderDetails['shipping_fee']); ?> Ks</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total:</span>
                                <span><?php echo number_format($orderDetails['total_amount']); ?> Ks</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="products.php" class="btn btn-outline-primary">Continue Shopping</a>
                        <a href="my-orders.php" class="btn btn-primary">View My Orders</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Unset the session variable to prevent re-showing the confirmation on refresh
unset($_SESSION['new_order']);
include 'includes/footer.php';
?>
<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'classes/Order.php';
require_once 'includes/auth.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: my-orders.php");
    exit();
}

$order_id = (int)$_GET['order_id'];
$user_id = $_SESSION['user_id'];

$db = new Database();
$order = new Order($db);

$orderDetails = $order->getOrderById($order_id, $user_id);

// Ensure the order belongs to the current user
if (!$orderDetails) {
    header("Location: my-orders.php");
    exit();
}

$page_title = "Order Confirmation";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg text-center">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h1 class="h3 mb-3">Thank You For Your Order!</h1>
                    <p class="text-muted">Your order has been placed successfully. A confirmation email has been sent to you.</p>
                    <p class="fw-bold">Order Number: <?php echo htmlspecialchars($orderDetails['order_number']); ?></p>
                    
                    <div class="card mt-4 text-start">
                        <div class="card-header">
                            Order Summary
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($orderDetails['items'] as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php echo htmlspecialchars($item['name']); ?>
                                        <small class="d-block text-muted">Quantity: <?php echo $item['quantity']; ?></small>
                                    </div>
                                    <span><?php echo number_format($item['total_price']); ?> Ks</span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <span><?php echo number_format($orderDetails['items_total']); ?> Ks</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Shipping:</span>
                                <span><?php echo number_format($orderDetails['shipping_fee']); ?> Ks</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total:</span>
                                <span><?php echo number_format($orderDetails['total_amount']); ?> Ks</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="products.php" class="btn btn-outline-primary">Continue Shopping</a>
                        <a href="my-orders.php" class="btn btn-primary">View My Orders</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Unset the session variable to prevent re-showing the confirmation on refresh
unset($_SESSION['new_order']);
include 'includes/footer.php';
?>
<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';

$db = new Database();

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message_text = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    
    // Validation
    $errors = [];
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (!$email) $errors[] = "Valid email is required";
    if (empty($subject)) $errors[] = "Subject is required";
    if (empty($message_text)) $errors[] = "Message is required";
    
    if (empty($errors)) {
        try {
            // Save to database
            $contact_id = $db->insert('contacts', [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'subject' => $subject,
                'message' => $message_text,
                'newsletter' => $newsletter,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($contact_id) {
                // Send email notification (optional)
                $to = SUPPORT_EMAIL;
                $email_subject = "New Contact Form Submission: $subject";
                $email_message = "Name: $first_name $last_name\nEmail: $email\nPhone: $phone\nSubject: $subject\n\nMessage:\n$message_text";
                $headers = "From: $email";
                
                mail($to, $email_subject, $email_message, $headers);
                
                $message = "Thank you for your message! We'll get back to you within 24 hours.";
                $message_type = "success";
            } else {
                $message = "There was an error sending your message. Please try again.";
                $message_type = "error";
            }
        } catch (Exception $e) {
            error_log("Contact form error: " . $e->getMessage());
            $message = "System error. Please try again later.";
            $message_type = "error";
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = "error";
    }
}

$page_title = "Contact Us";
include_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <h2 class="mb-4">Get in Touch</h2>
                    <p class="text-muted mb-4">Have a question or feedback? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select name="subject" class="form-select" required>
                                <option value="">Select a subject</option>
                                <option>General Inquiry</option>
                                <option>Product Support</option>
                                <option>Order Status</option>
                                <option>Return/Refund</option>
                                <option>Partnership</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="5" required placeholder="Tell us more about your inquiry..."></textarea>
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="newsletter" id="newsletter">
                            <label class="form-check-label" for="newsletter">
                                I'd like to receive updates about new products and special offers
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-send me-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Contact Information</h5>
                    
                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <i class="bi bi-geo-alt text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1">Store Address</h6>
                            <p class="text-muted mb-0">123 Shopping Street<br>Yangon, Myanmar</p>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <i class="bi bi-telephone text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1">Phone</h6>
                            <p class="text-muted mb-0">+95 1 234 5678<br>+95 9 876 5432</p>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <i class="bi bi-envelope text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1">Email</h6>
                            <p class="text-muted mb-0">support@onlineshop.com<br>info@onlineshop.com</p>
                        </div>
                    </div>
                    
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="bi bi-clock text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1">Business Hours</h6>
                            <p class="text-muted mb-0">Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 10:00 AM - 4:00 PM<br>Sunday: Closed</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Follow Us</h5>
                    <p class="text-muted mb-3">Stay connected for updates and special offers</p>
                    
                    <div class="d-flex gap-3">
                        <a href="#" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="#" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-twitter"></i>
                        </a>
                        <a href="#" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="#" class="btn btn-outline-dark btn-sm">
                            <i class="bi bi-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- FAQ Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <h3 class="mb-4">Frequently Asked Questions</h3>
                    
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    How long does shipping take?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Standard shipping typically takes 3-5 business days within Myanmar. Express shipping is available for 1-2 business day delivery.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    What is your return policy?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We offer a 30-day return policy for unused items in original packaging. Please contact our customer service team to initiate a return.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Do you offer international shipping?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Currently, we only ship within Myanmar. We are working on expanding our shipping options to international destinations.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    What payment methods do you accept?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We accept various payment methods including KPay, AyaPay, cash on delivery, bank transfers, and major credit/debit cards.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
