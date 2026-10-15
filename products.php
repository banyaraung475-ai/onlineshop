<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/classes/Product.php';

$db = new Database();
$product = new Product($db);
$category_id = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
$search = isset($_GET['search']) ? trim((string)$_GET['search']) : null;
$min_price = filter_input(INPUT_GET, 'min_price', FILTER_VALIDATE_FLOAT);
$max_price = filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT);
$sort = isset($_GET['sort']) ? trim((string)$_GET['sort']) : 'featured';
$categories = $product->getCategories();

// Backward-compatible category resolution for links using ?category=... or ?category_name=...
$category_param = isset($_GET['category']) ? trim((string)$_GET['category']) : '';
$category_name_param = isset($_GET['category_name']) ? trim((string)$_GET['category_name']) : '';

if (!$category_id && $category_param !== '') {
    if (ctype_digit($category_param)) {
        $category_id = (int)$category_param;
    } else {
        $category_name_param = $category_param;
    }
}

if (!$category_id && $category_name_param !== '') {
    foreach ($categories as $category) {
        if (strcasecmp($category['name'], $category_name_param) === 0) {
            $category_id = (int)$category['category_id'];
            break;
        }
    }
}

$selected_category_name = null;
if ($category_id) {
    foreach ($categories as $category) {
        if ((int)$category['category_id'] === (int)$category_id) {
            $selected_category_name = $category['name'];
            break;
        }
    }
}

$per_page = 12;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
if (!$page || $page < 1) {
    $page = 1;
}

if (!in_array($sort, ['featured', 'price_asc', 'price_desc', 'name_asc', 'name_desc'], true)) {
    $sort = 'featured';
}

if ($min_price !== null && $min_price < 0) {
    $min_price = 0;
}

if ($min_price !== null && $max_price !== null && $max_price < $min_price) {
    $temp = $min_price;
    $min_price = $max_price;
    $max_price = $temp;
}

// Get products
if ($category_id || $search || $min_price !== null || $max_price !== null || $sort) {
    $products = $product->getFilteredProducts($category_id, $search, $min_price, $max_price, $sort);
    if ($search) {
        $page_title = "Search Results for: " . htmlspecialchars($search);
    } elseif ($category_id) {
        $page_title = "Products - " . htmlspecialchars($selected_category_name ?? 'Category');
    } else {
        $page_title = "All Products";
    }
} else {
    $products = $product->getAllProducts();
    $page_title = "All Products";
}

$products_total = count($products);
$total_pages = (int)ceil($products_total / $per_page);
if ($products_total > 0) {
    $offset = ($page - 1) * $per_page;
    $products = array_slice($products, $offset, $per_page);
}

include_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mt-4">
    <div class="container">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
        </ol>
    </div>
</nav>

<!-- Products Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <!-- Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Search Products</h5>
                        <form method="GET" action="products.php">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search products..." 
                                       value="<?php echo htmlspecialchars($search ?? ''); ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Categories -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Categories</h5>
                        <div class="list-group list-group-flush">
                            <a href="products.php" class="list-group-item <?php echo !$category_id ? 'active' : ''; ?>">
                                <i class="bi bi-grid me-2"></i>All Products
                            </a>
                            <?php foreach ($categories as $category): ?>
                            <a href="products.php?category_id=<?php echo $category['category_id']; ?>" 
                               class="list-group-item <?php echo $category_id == $category['category_id'] ? 'active' : ''; ?>">
                                <i class="bi bi-tag me-2"></i><?php echo htmlspecialchars($category['name']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Price Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Price Range</h5>
                        <form method="GET" action="products.php">
                            <?php if ($category_id): ?>
                                <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                            <?php endif; ?>
                            <?php if (!empty($search)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label">Min Price</label>
                                <input type="number" class="form-control" name="min_price" placeholder="0" min="0" value="<?php echo $min_price !== null ? htmlspecialchars((string)$min_price) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Max Price</label>
                                <input type="number" class="form-control" name="max_price" placeholder="100000" min="0" value="<?php echo $max_price !== null ? htmlspecialchars((string)$max_price) : ''; ?>">
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100">Filter</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="col-lg-9">
                <!-- Results Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0"><?php echo $page_title; ?></h2>
                    <div class="d-flex align-items-center">
                        <span class="text-muted me-3"><?php echo $products_total; ?> products</span>
                        <form method="GET" action="products.php" class="mb-0">
                            <?php if ($category_id): ?>
                                <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                            <?php endif; ?>
                            <?php if (!empty($search)): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                            <?php if ($min_price !== null): ?>
                                <input type="hidden" name="min_price" value="<?php echo htmlspecialchars((string)$min_price); ?>">
                            <?php endif; ?>
                            <?php if ($max_price !== null): ?>
                                <input type="hidden" name="max_price" value="<?php echo htmlspecialchars((string)$max_price); ?>">
                            <?php endif; ?>
                            <select class="form-select form-select-sm mx-2" name="sort" onchange="this.form.submit()">
                                <option value="featured" <?php echo $sort === 'featured' ? 'selected' : ''; ?>>Sort by: Featured</option>
                                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                                <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                            </select>
                        </form>
                    </div>
                </div>

                <!-- Products Grid -->
                <?php if (!empty($products)): ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card product-card h-100 border-0 shadow-sm">
                            <div class="card-img-container">
                                <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/300'); ?>" 
                                     class="product-grid-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php if (!empty($product['is_featured']) && $product['is_featured']): ?>
                                    <span class="badge bg-warning position-absolute top-0 start-0 m-2">Featured</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text text-muted small">
                                    <?php echo substr(htmlspecialchars($product['description'] ?? ''), 0, 100) . '...'; ?>
                                </p>
                                <div class="mb-2">
                                    <small class="text-muted">Category: <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 text-primary mb-0"><?php echo formatPrice($product['price']); ?></span>
                                    <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-search text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">No products found</h4>
                    <p class="text-muted">Try adjusting your search or browse our categories.</p>
                    <a href="products.php" class="btn btn-primary">Browse All Products</a>
                </div>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if (!empty($products) && $total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php
                            $base_params = $_GET;
                            $prev_page = max(1, $page - 1);
                            $next_page = min($total_pages, $page + 1);
                            $base_params['page'] = $prev_page;
                            $prev_url = 'products.php' . (!empty($base_params) ? '?' . http_build_query($base_params) : '');
                            $base_params['page'] = $next_page;
                            $next_url = 'products.php' . (!empty($base_params) ? '?' . http_build_query($base_params) : '');
                        ?>
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo htmlspecialchars($prev_url); ?>" tabindex="-1">Previous</a>
                        </li>
                        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                            <?php
                                $base_params['page'] = $p;
                                $page_url = 'products.php' . (!empty($base_params) ? '?' . http_build_query($base_params) : '');
                            ?>
                            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo htmlspecialchars($page_url); ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo htmlspecialchars($next_url); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.product-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.card-img-container {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
}

.product-card .card-img-container {
    height: clamp(180px, 22vw, 260px);
}

.product-grid-image {
    display: block;
    width: 100%;
    height: 100%;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    object-position: center;
    padding: 0.5rem;
    transition: transform 0.3s ease;
}

.product-card:hover .product-grid-image {
    transform: scale(1.05);
}

.breadcrumb-item + .breadcrumb-item::before {
    content: ">";
    color: #6c757d;
}

.list-group-item.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

@media (max-width: 575.98px) {
    .product-card .card-img-container {
        height: 190px;
    }
}
</style>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
