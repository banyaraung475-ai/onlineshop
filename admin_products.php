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

// Add missing columns if they don't exist
try {
    // Check if is_active column exists in categories
    $columns = $db->fetchAll("SHOW COLUMNS FROM categories LIKE 'is_active'");
    if (empty($columns)) {
        $db->query('ALTER TABLE categories ADD COLUMN is_active BOOLEAN DEFAULT TRUE');
    }
    
    // Check if min_stock_level column exists in products
    $columns = $db->fetchAll("SHOW COLUMNS FROM products LIKE 'min_stock_level'");
    if (empty($columns)) {
        $db->query('ALTER TABLE products ADD COLUMN min_stock_level INT DEFAULT 5');
    }
} catch (Exception $e) {
    // Ignore errors
}

$message = '';
$error = '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
        // Proceed with form handling
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_product') {
            // Handle add product
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = (float)$_POST['price'];
            $category_id = (int)$_POST['category_id'];
            $stock_quantity = (int)$_POST['stock_quantity'];
            $sku = trim($_POST['sku']);
            $is_active = (int)($_POST['is_active'] ?? 0);

            if (empty($name) || $price <= 0 || empty($sku)) {
                throw new Exception("Name, SKU, and valid price are required.");
            }

            // Check if SKU already exists
            $existing = $db->fetchOne("SELECT product_id FROM products WHERE sku = ?", [$sku]);
            if ($existing) {
                throw new Exception("SKU already exists.");
            }

            $image_url = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/assets/images/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $file_name = uniqid() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $file_name;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = 'assets/images/products/' . $file_name;
                }
            }

            $db->insert('products', [
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'category_id' => $category_id,
                'stock_quantity' => $stock_quantity,
                'sku' => $sku,
                'image_url' => $image_url,
                'is_active' => $is_active
            ]);
            $message = "Product added successfully.";
        } elseif ($action === 'update_product') {
            // Handle update product
            $product_id = (int)$_POST['product_id'];
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = (float)$_POST['price'];
            $category_id = (int)$_POST['category_id'];
            $stock_quantity = (int)$_POST['stock_quantity'];
            $sku = trim($_POST['sku']);
            $is_active = (int)($_POST['is_active'] ?? 0);

            if (empty($name) || $price <= 0 || empty($sku)) {
                throw new Exception("Name, SKU, and valid price are required.");
            }

            // Check if SKU already exists (excluding current product)
            $existing = $db->fetchOne("SELECT product_id FROM products WHERE sku = ? AND product_id != ?", [$sku, $product_id]);
            if ($existing) {
                throw new Exception("SKU already exists.");
            }

            $update_data = [
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'category_id' => $category_id,
                'stock_quantity' => $stock_quantity,
                'sku' => $sku,
                'is_active' => $is_active
            ];

            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/assets/images/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $file_name = uniqid() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $file_name;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $update_data['image_url'] = 'assets/images/products/' . $file_name;
                }
            }

            $db->update('products', $update_data, 'product_id = ?', [$product_id]);
            $message = "Product updated successfully.";
        } elseif ($action === 'delete_product') {
            $product_id = (int)$_POST['product_id'];
            $db->delete('products', 'product_id = ?', [$product_id]);
            $message = "Product deleted successfully.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch products
$products = $db->fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id ORDER BY p.created_at DESC");

// Fetch categories for dropdown
$categories = $db->fetchAll("SELECT category_id, name FROM categories ORDER BY name");

// Stats
$total_products = $db->fetchOne("SELECT COUNT(*) as count FROM products")['count'];
$active_products = $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE is_active = 1")['count'];
$low_stock = $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= min_stock_level")['count'];

$page_title = "Manage Products";
include_once __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-sidebar">
    <div class="p-4">
        <h4 class="text-white mb-4"><?php echo SITE_NAME; ?> Admin</h4>
        <nav class="admin-nav">
            <a href="admin.php" class="admin-nav-link">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
            <a href="admin_products.php" class="admin-nav-link active">
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
        <h1 class="h3 mb-0">Manage Products</h1>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-circle me-2"></i>Add Product
            </button>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="text" class="form-control" id="product-search" placeholder="Search products...">
                </div>
                <div class="col-md-3">
                    <select class="form-select">
                        <option selected>All Categories</option>
                        <option>Electronics</option>
                        <option>Clothing</option>
                        <option>Home</option>
                        <option>Books</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select">
                        <option selected>All Status</option>
                        <option>Active</option>
                        <option>Inactive</option>
                        <option>Out of Stock</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Products</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="product-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" class="form-check-input">
                            </th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No products found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><input type="checkbox" class="form-check-input product-checkbox" value="<?php echo $product['product_id']; ?>"></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $product['image_url'] ?: 'https://via.placeholder.com/50'; ?>" alt="Product" class="rounded me-3" style="width: 50px; height: 50px;">
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($product['name'] ?? ''); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 50)); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($product['sku'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?: 'No Category'); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock_quantity']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $product['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary edit-product-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editProductModal"
                                            data-product-id="<?php echo $product['product_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($product['name'] ?? ''); ?>"
                                            data-description="<?php echo htmlspecialchars($product['description'] ?? ''); ?>"
                                            data-price="<?php echo $product['price']; ?>"
                                            data-category-id="<?php echo $product['category_id']; ?>"
                                            data-stock-quantity="<?php echo $product['stock_quantity']; ?>"
                                            data-sku="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>"
                                            data-is-active="<?php echo $product['is_active']; ?>">
                                        Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            Delete
                                        </button>
                                    </form>
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

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addProductForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_product">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Product Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">SKU *</label>
                                <input type="text" class="form-control" name="sku" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category *</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Price *</label>
                                <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Stock Quantity *</label>
                                <input type="number" class="form-control" name="stock_quantity" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product Image</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <label class="form-check-label">
                                Active (visible to customers)
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addProductForm" class="btn btn-primary">Add Product</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editProductForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_product">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Product Name *</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">SKU *</label>
                                <input type="text" class="form-control" name="sku" id="edit_sku" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category *</label>
                        <select class="form-select" name="category_id" id="edit_category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Price *</label>
                                <input type="number" class="form-control" name="price" id="edit_price" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Stock Quantity *</label>
                                <input type="number" class="form-control" name="stock_quantity" id="edit_stock_quantity" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product Image</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                        <small class="text-muted">Leave empty to keep current image</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                            <label class="form-check-label" for="edit_is_active">
                                Active (visible to customers)
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editProductForm" class="btn btn-primary">Update Product</button>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
