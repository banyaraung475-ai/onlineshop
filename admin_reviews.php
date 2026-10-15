<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';

// Only allow admin users
$currentRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? '');
if (!isLoggedIn() || $currentRole !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = new Database();
$message = '';
$error = '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle moderation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';
        $reviewId = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;

        try {
            if ($reviewId <= 0) {
                throw new Exception("Invalid review ID.");
            }

            if ($action === 'approve_review') {
                $db->update('product_reviews', ['is_approved' => 1], 'review_id = ?', [$reviewId]);
                $message = "Review approved successfully.";
            } elseif ($action === 'unapprove_review') {
                $db->update('product_reviews', ['is_approved' => 0], 'review_id = ?', [$reviewId]);
                $message = "Review moved to pending successfully.";
            } else {
                throw new Exception("Invalid action.");
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Filter
$statusFilter = $_GET['status'] ?? 'all';
$statusFilter = in_array($statusFilter, ['all', 'approved', 'pending'], true) ? $statusFilter : 'all';

$whereSql = '';
$params = [];
if ($statusFilter === 'approved') {
    $whereSql = 'WHERE pr.is_approved = 1';
} elseif ($statusFilter === 'pending') {
    $whereSql = 'WHERE pr.is_approved = 0';
}

// Fetch stats
$totalReviews = (int)($db->fetchOne("SELECT COUNT(*) AS count FROM product_reviews")['count'] ?? 0);
$approvedReviews = (int)($db->fetchOne("SELECT COUNT(*) AS count FROM product_reviews WHERE is_approved = 1")['count'] ?? 0);
$pendingReviews = (int)($db->fetchOne("SELECT COUNT(*) AS count FROM product_reviews WHERE is_approved = 0")['count'] ?? 0);

// Fetch reviews
$reviews = $db->fetchAll("
    SELECT
        pr.review_id,
        pr.product_id,
        pr.user_id,
        pr.rating,
        pr.review_text,
        pr.is_approved,
        pr.created_at,
        p.name AS product_name,
        COALESCE(NULLIF(u.full_name, ''), u.username, 'Customer') AS customer_name
    FROM product_reviews pr
    LEFT JOIN products p ON pr.product_id = p.product_id
    LEFT JOIN users u ON pr.user_id = u.user_id
    {$whereSql}
    ORDER BY pr.created_at DESC
    LIMIT 100
", $params);

$page_title = "Manage Reviews";
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
            <a href="admin_reviews.php" class="admin-nav-link active">
                <i class="bi bi-chat-left-text me-2"></i> Reviews
            </a>
        </nav>
    </div>
</div>

<div class="admin-main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Manage Customer Reviews</h1>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalReviews; ?></div>
                <div class="stat-label">Total Reviews</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number"><?php echo $approvedReviews; ?></div>
                <div class="stat-label">Approved</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number"><?php echo $pendingReviews; ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex gap-2 flex-wrap">
                <a href="admin_reviews.php?status=all" class="btn btn-sm <?php echo $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                <a href="admin_reviews.php?status=approved" class="btn btn-sm <?php echo $statusFilter === 'approved' ? 'btn-success' : 'btn-outline-success'; ?>">Approved</a>
                <a href="admin_reviews.php?status=pending" class="btn btn-sm <?php echo $statusFilter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">Pending</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Reviews</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Rating</th>
                            <th>Review</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No reviews found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td>#<?php echo (int)$review['review_id']; ?></td>
                            <td>
                                <a href="product-detail.php?id=<?php echo (int)$review['product_id']; ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo htmlspecialchars($review['product_name'] ?? ('Product #' . (int)$review['product_id'])); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($review['customer_name']); ?></td>
                            <td><?php echo (int)$review['rating']; ?>/5</td>
                            <td style="max-width: 340px;"><?php echo htmlspecialchars($review['review_text']); ?></td>
                            <td>
                                <?php if ((int)$review['is_approved'] === 1): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($review['created_at'])); ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="review_id" value="<?php echo (int)$review['review_id']; ?>">
                                    <?php if ((int)$review['is_approved'] === 1): ?>
                                        <input type="hidden" name="action" value="unapprove_review">
                                        <button type="submit" class="btn btn-sm btn-outline-warning">Set Pending</button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="approve_review">
                                        <button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                                    <?php endif; ?>
                                </form>
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

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
