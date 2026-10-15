<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/classes/Product.php';
require_once __DIR__ . '/includes/auth.php';

// Get product ID from URL
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$productId) {
    redirect('products.php');
}

$db = new Database();
$product = new Product($db);

// Get product details
$productData = $product->getProductById($productId);

if (!$productData) {
    redirect('products.php');
}

// Build gallery images from main + additional images.
$galleryImages = [];
$mainImage = trim((string)($productData['image_url'] ?? ''));
if ($mainImage !== '') {
    $galleryImages[] = $mainImage;
}

$additionalImages = [];
$additionalImagesRaw = $productData['additional_images'] ?? null;
if (is_string($additionalImagesRaw) && $additionalImagesRaw !== '') {
    $decoded = json_decode($additionalImagesRaw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $additionalImages = $decoded;
    } else {
        $additionalImages = array_map('trim', explode(',', $additionalImagesRaw));
    }
} elseif (is_array($additionalImagesRaw)) {
    $additionalImages = $additionalImagesRaw;
}

foreach ($additionalImages as $image) {
    if (!is_string($image)) {
        continue;
    }
    $image = trim($image);
    if ($image !== '') {
        $galleryImages[] = $image;
    }
}

$galleryImages = array_values(array_unique($galleryImages));
if (empty($galleryImages)) {
    $galleryImages[] = 'https://via.placeholder.com/600';
}

// Handle review submission
$review_message = '';
$review_message_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!ENABLE_REVIEWS) {
        $review_message = 'Reviews are currently disabled.';
        $review_message_type = 'warning';
    } elseif (!isLoggedIn()) {
        $review_message = 'Please log in to submit a review.';
        $review_message_type = 'danger';
    } else {
        $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
        $reviewText = trim($_POST['review_text'] ?? '');

        if ($rating === false || $rating < 1 || $rating > 5) {
            $review_message = 'Please select a valid rating between 1 and 5.';
            $review_message_type = 'danger';
        } elseif ($reviewText === '' || mb_strlen($reviewText) < 10) {
            $review_message = 'Review text must be at least 10 characters.';
            $review_message_type = 'danger';
        } else {
            try {
                $existingReview = $db->fetchOne(
                    "SELECT review_id FROM product_reviews WHERE product_id = ? AND user_id = ? LIMIT 1",
                    [$productId, (int) $_SESSION['user_id']]
                );

                if ($existingReview) {
                    $review_message = 'You have already submitted a review for this product.';
                    $review_message_type = 'warning';
                } else {
                    $db->insert('product_reviews', [
                        'product_id' => $productId,
                        'user_id' => (int) $_SESSION['user_id'],
                        'rating' => $rating,
                        'review_text' => $reviewText,
                        'is_verified' => 0,
                        'is_approved' => AUTO_APPROVE_REVIEWS ? 1 : 0,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);

                    $review_message = AUTO_APPROVE_REVIEWS
                        ? 'Thank you! Your review has been posted.'
                        : 'Thank you! Your review was submitted and is awaiting approval.';
                    $review_message_type = 'success';
                }
            } catch (Exception $e) {
                $review_message = 'Unable to submit your review right now. Please try again.';
                $review_message_type = 'danger';
            }
        }
    }
}

// Get related products
$relatedProducts = $product->getProductsByCategory($productData['category_id'], 4);

// Get review statistics
$reviewStats = $db->fetchOne("
    SELECT COUNT(*) AS total_reviews, COALESCE(AVG(rating), 0) AS average_rating
    FROM product_reviews
    WHERE product_id = ? AND is_approved = 1
", [$productId]);
$totalReviews = (int)($reviewStats['total_reviews'] ?? 0);
$averageRating = (float)($reviewStats['average_rating'] ?? 0);

// Get product reviews
$reviews = $db->fetchAll("
    SELECT pr.*, COALESCE(NULLIF(u.full_name, ''), u.username, 'Customer') AS reviewer_name
    FROM product_reviews pr
    LEFT JOIN users u ON pr.user_id = u.user_id
    WHERE pr.product_id = ? AND pr.is_approved = 1
    ORDER BY pr.created_at DESC
    LIMIT 5
", [$productId]);

$page_title = htmlspecialchars($productData['name']);
include_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mt-4">
    <div class="container">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php">Products</a></li>
            <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
        </ol>
    </div>
</nav>

<!-- Product Detail -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Product Images -->
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="card border-0 shadow-sm product-media-card">
                    <div class="card-body">
                        <div class="main-image-frame mb-3">
                            <img src="<?php echo htmlspecialchars($galleryImages[0]); ?>" 
                                 class="main-product-image" alt="<?php echo htmlspecialchars($productData['name']); ?>"
                                 id="mainImage">
                        </div>
                        <div class="thumbnail-images">
                            <?php
                            foreach ($galleryImages as $index => $image):
                            ?>
                            <img src="<?php echo htmlspecialchars($image); ?>" 
                                 class="thumbnail rounded <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 alt="Thumbnail <?php echo $index + 1; ?>"
                                 onclick='changeMainImage(this, <?php echo json_encode($image, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Product Info -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h1 class="mb-3"><?php echo htmlspecialchars($productData['name']); ?></h1>
                        
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <?php
                                $rating = $averageRating;
                                for ($i = 1; $i <= 5; $i++):
                                    if ($i <= floor($rating)) {
                                        echo '<i class="bi bi-star-fill text-warning"></i>';
                                    } else {
                                        echo '<i class="bi bi-star text-warning"></i>';
                                    }
                                endfor;
                                ?>
                                <span class="text-muted">(<?php echo $totalReviews; ?> reviews)</span>
                            </div>
                            <?php if (!empty($productData['is_featured']) && $productData['is_featured']): ?>
                                <span class="badge bg-warning">Featured</span>
                            <?php endif; ?>
                            <?php if (!empty($productData['is_active']) && $productData['is_active']): ?>
                                <span class="badge bg-success">In Stock</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Out of Stock</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <h3 class="text-primary"><?php echo formatPrice($productData['price']); ?></h3>
                            <?php if (!empty($productData['sale_price']) && $productData['sale_price'] < $productData['price']): ?>
                                <small class="text-muted text-decoration-line-through"><?php echo formatPrice($productData['price']); ?></small>
                                <span class="text-danger">Save <?php echo formatPrice($productData['price'] - $productData['sale_price']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Description</h5>
                            <p><?php echo nl2br(htmlspecialchars($productData['description'] ?? 'No description available.')); ?></p>
                        </div>
                        
                        <!-- Product Details -->
                        <div class="mb-4">
                            <h5>Product Details</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>SKU:</strong></td>
                                    <td><?php echo htmlspecialchars($productData['sku'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Category:</strong></td>
                                    <td><?php echo htmlspecialchars($productData['category_name'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Stock:</strong></td>
                                    <td><?php echo $productData['stock_quantity']; ?> units</td>
                                </tr>
                                <tr>
                                    <td><strong>Weight:</strong></td>
                                    <td><?php echo $productData['weight'] ?? 'N/A'; ?> kg</td>
                                </tr>
                                <tr>
                                    <td><strong>Dimensions:</strong></td>
                                    <td><?php echo htmlspecialchars($productData['dimensions'] ?? 'N/A'); ?></td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Quantity and Add to Cart -->
                        <div class="mb-4">
                            <h5>Quantity</h5>
                            <div class="input-group" style="max-width: 200px;">
                                <button class="btn btn-outline-secondary" type="button" onclick="decreaseQuantity()">-</button>
                                <input type="number" class="form-control text-center" id="quantity" value="1" min="1" max="<?php echo $productData['stock_quantity']; ?>">
                                <button class="btn btn-outline-secondary" type="button" onclick="increaseQuantity()">+</button>
                            </div>
                            
                            <div class="mt-3">
                                <button class="btn btn-primary btn-lg w-100" onclick="addToCart(<?php echo $productData['product_id']; ?>)">
                                    <i class="bi bi-cart-plus me-2"></i>Add to Cart
                                </button>
                                <button class="btn btn-outline-primary btn-lg w-100 mt-2" onclick="addToWishlist(<?php echo $productData['product_id']; ?>)">
                                    <i class="bi bi-heart me-2"></i>Add to Wishlist
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Tabs -->
        <div class="row mt-5">
            <div class="col-12">
                <ul class="nav nav-tabs" id="productTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="true">Description</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="specifications-tab" data-bs-toggle="tab" data-bs-target="#specifications" type="button" role="tab" aria-controls="specifications" aria-selected="false">Specifications</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab" aria-controls="reviews" aria-selected="false">Reviews</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping" type="button" role="tab" aria-controls="shipping" aria-selected="false">Shipping</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="productTabsContent">
                    <div class="tab-pane fade show active" id="description" role="tabpanel">
                        <h4>Product Description</h4>
                        <p><?php echo nl2br(htmlspecialchars($productData['description'] ?? 'No detailed description available for this product.')); ?></p>
                    </div>
                    
                    <div class="tab-pane fade" id="specifications" role="tabpanel">
                        <h4>Specifications</h4>
                        <table class="table table-striped">
                            <tr>
                                <th>Brand</th>
                                <td><?php echo htmlspecialchars($productData['brand'] ?? 'Generic'); ?></td>
                            </tr>
                            <tr>
                                <th>Model</th>
                                <td><?php echo htmlspecialchars($productData['sku'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Material</th>
                                <td><?php echo htmlspecialchars($productData['material'] ?? 'Standard'); ?></td>
                            </tr>
                            <tr>
                                <th>Color</th>
                                <td><?php echo htmlspecialchars($productData['color'] ?? 'As shown'); ?></td>
                            </tr>
                            <tr>
                                <th>Warranty</th>
                                <td><?php echo htmlspecialchars($productData['warranty'] ?? '1 Year'); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="tab-pane fade" id="reviews" role="tabpanel">
                        <h4>Customer Reviews</h4>
                        <?php if ($review_message !== ''): ?>
                            <div class="alert alert-<?php echo htmlspecialchars($review_message_type); ?>">
                                <?php echo htmlspecialchars($review_message); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($reviews)): ?>
                            <?php foreach ($reviews as $review): ?>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($review['reviewer_name']); ?></h6>
                                        <div class="mb-2">
                                            <?php
                                            for ($i = 1; $i <= 5; $i++):
                                                if ($i <= $review['rating']) {
                                                    echo '<i class="bi bi-star-fill text-warning"></i>';
                                                } else {
                                                    echo '<i class="bi bi-star text-warning"></i>';
                                                }
                                            endfor;
                                            ?>
                                        </div>
                                        <p class="mb-0"><?php echo htmlspecialchars($review['review_text']); ?></p>
                                    </div>
                                    <small class="text-muted"><?php echo formatDate($review['created_at']); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No reviews yet. Be the first to review this product!</p>
                        <?php endif; ?>
                        
                        <?php if (ENABLE_REVIEWS): ?>
                            <div class="mt-4">
                                <h5>Write a Review</h5>
                                <?php if (isLoggedIn()): ?>
                                    <form method="POST" action="product-detail.php?id=<?php echo (int)$productId; ?>">
                                        <div class="mb-3">
                                            <label for="rating" class="form-label">Rating</label>
                                            <select id="rating" name="rating" class="form-select" required>
                                                <option value="">Select rating</option>
                                                <option value="5">5 - Excellent</option>
                                                <option value="4">4 - Very Good</option>
                                                <option value="3">3 - Good</option>
                                                <option value="2">2 - Fair</option>
                                                <option value="1">1 - Poor</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="review_text" class="form-label">Review</label>
                                            <textarea
                                                id="review_text"
                                                name="review_text"
                                                class="form-control"
                                                rows="4"
                                                minlength="10"
                                                maxlength="1000"
                                                required
                                            ></textarea>
                                        </div>
                                        <button type="submit" name="submit_review" class="btn btn-outline-primary">Submit Review</button>
                                    </form>
                                <?php else: ?>
                                    <p class="text-muted mb-0">Please <a href="login.php">log in</a> to write a review.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-pane fade" id="shipping" role="tabpanel">
                        <h4>Shipping Information</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Standard Shipping</h6>
                                <ul>
                                    <li>3-5 business days</li>
                                    <li>Free shipping on orders over <?php echo formatPrice(FREE_SHIPPING_THRESHOLD); ?></li>
                                    <li>Tracking available</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Express Shipping</h6>
                                <ul>
                                    <li>1-2 business days</li>
                                    <li><?php echo formatPrice(EXPRESS_SHIPPING_COST); ?></li>
                                    <li>Priority handling</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6>Pickup Available</h6>
                                <ul>
                                    <li>Available tomorrow</li>
                                    <li>No shipping cost</li>
                                    <li>Self-pickup only</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>International Shipping</h6>
                                <ul>
                                    <li>7-14 business days</li>
                                    <li>International rates apply</li>
                                    <li>Customs clearance</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <div class="row mt-5">
            <div class="col-12">
                <h3>Related Products</h3>
                <div class="row g-4">
                    <?php if (!empty($relatedProducts)): ?>
                        <?php foreach ($relatedProducts as $relatedProduct): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm related-product-card">
                                <div class="related-product-image-frame">
                                    <img src="<?php echo htmlspecialchars($relatedProduct['image_url'] ?? 'https://via.placeholder.com/200'); ?>" 
                                         class="card-img-top related-product-image" alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>">
                                </div>
                                <div class="card-body text-center">
                                    <h6 class="card-title"><?php echo htmlspecialchars($relatedProduct['name']); ?></h6>
                                    <p class="card-text text-primary fw-bold"><?php echo formatPrice($relatedProduct['price']); ?></p>
                                    <a href="product-detail.php?id=<?php echo $relatedProduct['product_id']; ?>" class="btn btn-outline-primary btn-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No related products found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function changeMainImage(thumbnail, imageSrc) {
    document.getElementById('mainImage').src = imageSrc;
    document.querySelectorAll('.thumbnail').forEach((img) => img.classList.remove('active'));
    if (thumbnail) {
        thumbnail.classList.add('active');
    }
}

function increaseQuantity() {
    const quantityInput = document.getElementById('quantity');
    const maxValue = parseInt(quantityInput.max);
    let currentValue = parseInt(quantityInput.value);
    
    if (currentValue < maxValue) {
        quantityInput.value = currentValue + 1;
    }
}

function decreaseQuantity() {
    const quantityInput = document.getElementById('quantity');
    let currentValue = parseInt(quantityInput.value);
    
    if (currentValue > 1) {
        quantityInput.value = currentValue - 1;
    }
}

function addToCart(productId) {
    const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
    if (!isLoggedIn) {
        const returnUrl = encodeURIComponent(window.location.pathname + window.location.search);
        window.location.href = `login.php?redirect=${returnUrl}`;
        return;
    }

    const quantity = parseInt(document.getElementById('quantity').value);
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch('api/cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product added to cart!', 'success');
            updateCartCount();
        } else {
            showNotification(data.message || 'Failed to add to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

function addToWishlist(productId) {
    const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
    if (!isLoggedIn) {
        const returnUrl = encodeURIComponent(window.location.pathname + window.location.search);
        window.location.href = `login.php?redirect=${returnUrl}`;
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);

    fetch('api/wishlist.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        if (response.status === 401) {
            showNotification('Please log in to add items to your wishlist.', 'error');
            return null;
        }
        if (response.status === 403) {
            showNotification('Wishlist is currently disabled.', 'error');
            return null;
        }

        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const message = data.message || 'Failed to add to wishlist';
            throw new Error(message);
        }
        return data;
    })
    .then(data => {
        if (!data) {
            return;
        }
        if (data.success) {
            showNotification(data.message || 'Added to wishlist!', 'success');
        } else {
            showNotification(data.message || 'Failed to add to wishlist', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification(error.message || 'An error occurred. Please try again.', 'error');
    });
}
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="me-2">
                ${type === 'success' ? '<i class="bi bi-check-circle"></i>' : '<i class="bi bi-exclamation-circle"></i>'}
            </div>
            <div>${message}</div>
            <button type="button" class="btn-close ms-2" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 3000);
}

function updateCartCount() {
    fetch('api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const cartBadge = document.querySelector('.cart-count');
            if (cartBadge) {
                cartBadge.textContent = data.cart_count;
            }
        }
    })
    .catch(error => {
        console.error('Error updating cart count:', error);
    });
}
</script>

<style>
.product-media-card {
    position: sticky;
    top: 1rem;
}

.main-image-frame {
    width: 100%;
    aspect-ratio: 1 / 1;
    background: #f8f9fa;
    border-radius: 0.5rem;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.main-product-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.thumbnail-images {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    padding-bottom: 0.25rem;
    scrollbar-width: thin;
}

.thumbnail {
    width: 72px;
    height: 72px;
    object-fit: cover;
    cursor: pointer;
    border: 2px solid transparent;
    transition: transform 0.2s ease, border-color 0.2s ease;
    flex: 0 0 auto;
}

.thumbnail:hover {
    transform: scale(1.05);
}

.thumbnail.active {
    border-color: #0d6efd;
}

.related-product-image-frame {
    width: 100%;
    aspect-ratio: 1 / 1;
    background: #f8f9fa;
    overflow: hidden;
}

.related-product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.nav-tabs {
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    white-space: nowrap;
}

.nav-tabs .nav-link {
    color: #6c757d;
    border: 1px solid transparent;
    border-bottom: 2px solid transparent;
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    border-color: #dee2e6 #dee2e6 #0d6efd;
}

.nav-tabs .nav-link:hover {
    border-color: #e9ecef #e9ecef #e9ecef;
}

@media (max-width: 991.98px) {
    .product-media-card {
        position: static;
    }
}

@media (max-width: 767.98px) {
    .main-image-frame {
        aspect-ratio: 4 / 3;
    }

    .thumbnail {
        width: 58px;
        height: 58px;
    }

    .nav-tabs .nav-link {
        padding: 0.6rem 0.85rem;
        font-size: 0.9rem;
    }
}
</style>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
