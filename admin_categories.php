<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';

// Only allow admin users
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'add_category') {
                $name = trim((string)($_POST['name'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
                $image_url = trim((string)($_POST['image_url'] ?? ''));
                $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                if ($name === '') {
                    throw new Exception("Category name is required.");
                }

                $db->insert('categories', [
                    'name' => $name,
                    'description' => $description,
                    'parent_id' => $parent_id,
                    'image_url' => $image_url,
                    'sort_order' => $sort_order,
                    'is_active' => $is_active
                ]);
                $message = "Category added successfully.";
            } elseif ($action === 'update_category') {
                $category_id = (int)($_POST['category_id'] ?? 0);
                $name = trim((string)($_POST['name'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
                $image_url = trim((string)($_POST['image_url'] ?? ''));
                $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                if ($category_id <= 0) {
                    throw new Exception("Invalid category.");
                }
                if ($name === '') {
                    throw new Exception("Category name is required.");
                }

                $db->update('categories', [
                    'name' => $name,
                    'description' => $description,
                    'parent_id' => $parent_id,
                    'image_url' => $image_url,
                    'sort_order' => $sort_order,
                    'is_active' => $is_active
                ], 'category_id = ?', [$category_id]);
                $message = "Category updated successfully.";
            } elseif ($action === 'delete_category') {
                $category_id = (int)($_POST['category_id'] ?? 0);
                if ($category_id <= 0) {
                    throw new Exception("Invalid category.");
                }
                $db->delete('categories', 'category_id = ?', [$category_id]);
                $message = "Category deleted successfully.";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Fetch categories with product counts
$categoryColumns = [];
try {
    $categoryColumns = array_column($db->getTableInfo('categories'), 'Field');
} catch (Exception $e) {
    $categoryColumns = [];
}
$orderBy = in_array('sort_order', $categoryColumns, true)
    ? 'c.sort_order ASC, c.name ASC'
    : 'c.name ASC';

$categories = $db->fetchAll("
    SELECT c.*,
           (SELECT COUNT(*) FROM products p WHERE p.category_id = c.category_id) AS product_count
    FROM categories c
    ORDER BY {$orderBy}
");

$page_title = "Manage Categories";
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
            <a href="admin_categories.php" class="admin-nav-link active">
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
        <h1 class="h3 mb-0">Manage Categories</h1>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-circle me-2"></i>Add Category
            </button>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Categories Grid -->
    <div class="row">
        <?php if (empty($categories)): ?>
            <div class="col-12">
                <div class="text-center text-muted py-5">No categories found.</div>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $category): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <span class="badge bg-primary"><?php echo (int)$category['product_count']; ?> products</span>
                        </div>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($category['description'] ?? ''); ?></p>
                        <div class="d-flex gap-2">
                            <button
                                class="btn btn-sm btn-outline-primary edit-category-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#editCategoryModal"
                                data-category-id="<?php echo $category['category_id']; ?>"
                                data-name="<?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-description="<?php echo htmlspecialchars($category['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-parent-id="<?php echo $category['parent_id'] ?? ''; ?>"
                                data-image-url="<?php echo htmlspecialchars($category['image_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-sort-order="<?php echo (int)($category['sort_order'] ?? 0); ?>"
                                data-is-active="<?php echo (int)($category['is_active'] ?? 0); ?>"
                            >
                                Edit
                            </button>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                <input type="hidden" name="action" value="delete_category">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_category">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter category name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Enter category description"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent Category</label>
                        <select class="form-select" name="parent_id">
                            <option value="">None (Top Level)</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image URL</label>
                        <input type="url" class="form-control" name="image_url" placeholder="https://example.com/image.jpg">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" class="form-control" name="sort_order" value="0" min="0">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="activeCheck" name="is_active" value="1" checked>
                        <label class="form-check-label" for="activeCheck">
                            Active
                        </label>
                    </div>
                </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Category</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_category">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="category_id" id="edit-category-id">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" id="edit-name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit-description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent Category</label>
                        <select class="form-select" name="parent_id" id="edit-parent-id">
                            <option value="">None (Top Level)</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image URL</label>
                        <input type="url" class="form-control" name="image_url" id="edit-image-url">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" class="form-control" name="sort_order" id="edit-sort-order" min="0">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="edit-is-active" name="is_active" value="1">
                        <label class="form-check-label" for="edit-is-active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editCategoryModal');
    if (!editModal) return;

    editModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        if (!button) return;

        document.getElementById('edit-category-id').value = button.getAttribute('data-category-id');
        document.getElementById('edit-name').value = button.getAttribute('data-name') || '';
        document.getElementById('edit-description').value = button.getAttribute('data-description') || '';
        document.getElementById('edit-parent-id').value = button.getAttribute('data-parent-id') || '';
        document.getElementById('edit-image-url').value = button.getAttribute('data-image-url') || '';
        document.getElementById('edit-sort-order').value = button.getAttribute('data-sort-order') || 0;
        document.getElementById('edit-is-active').checked = button.getAttribute('data-is-active') == 1;
    });
});
</script>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
