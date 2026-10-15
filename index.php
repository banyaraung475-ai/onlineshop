<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/classes/Product.php';

$db = new Database();
$product = new Product($db);
$featuredProducts = $product->getAllProducts();
$categories = $product->getCategories();

// Handle newsletter subscription
$newsletter_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter_email'])) {
    if (!isLoggedIn()) {
        $newsletter_message = 'Please log in to subscribe to the newsletter.';
    } else {
        $email = filter_input(INPUT_POST, 'newsletter_email', FILTER_VALIDATE_EMAIL);
    
        if ($email) {
            try {
                $db->insert('newsletter_subscribers', [
                    'user_id' => (int) $_SESSION['user_id'],
                    'email' => $email,
                    'is_active' => true,
                    'subscribed_at' => date('Y-m-d H:i:s')
                ]);
                $newsletter_message = 'Thank you for subscribing to our newsletter!';
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $newsletter_message = 'You are already subscribed to our newsletter.';
                } else {
                    $newsletter_message = 'There was an error subscribing. Please try again.';
                }
            }
        } else {
            $newsletter_message = 'Please enter a valid email address.';
        }
    }
}

$page_title = "Home";
include_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-6">
                <div class="hero-content">
                    <h1 class="display-4 fw-bold text-white mb-4">Welcome to <?php echo SITE_NAME; ?></h1>
                    <p class="lead text-white mb-4"><?php echo SITE_DESCRIPTION; ?></p>
                    <div class="d-flex gap-3">
                        <a href="products.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-bag me-2"></i>Shop Now
                        </a>
                        <a href="#featured" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-star me-2"></i>Featured Products
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image">
                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=600&h=400&fit=crop" 
                         alt="Shopping" class="img-fluid rounded-3 shadow-lg">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section id="featured" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold">Featured Products</h2>
            <p class="lead text-muted">Discover our handpicked selection of premium products</p>
        </div>
        
        <div class="row g-4">
            <?php if (!empty($featuredProducts)): ?>
                <?php foreach (array_slice($featuredProducts, 0, 8) as $product): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card product-card h-100 border-0 shadow-sm">
                        <div class="card-img-container">
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/300'); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php if (!empty($product['is_featured']) && $product['is_featured']): ?>
                                <span class="badge bg-warning position-absolute top-0 start-0 m-2">Featured</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted small">
                                <?php echo substr(htmlspecialchars($product['description'] ?? ''), 0, 80) . '...'; ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center product-meta">
                                <span class="h5 text-primary mb-0"><?php echo formatPrice($product['price']); ?></span>
                                <button class="btn btn-sm btn-outline-primary add-to-cart-btn product-cart-btn" data-product-id="<?php echo $product['product_id']; ?>" data-quantity="1">
                                    <i class="bi bi-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="text-center py-5 text-muted">No products available.</div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="products.php" class="btn btn-primary btn-lg">
                <i class="bi bi-grid me-2"></i>View All Products
            </a>
        </div>
    </div>
</section>

<!-- Categories -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold">Shop by Category</h2>
            <p class="lead text-muted">Browse our wide range of categories</p>
        </div>
        
        <div class="row g-4">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card category-card h-100 border-0 shadow-sm">
                        <div class="card-img-container">
                            <img src="<?php echo htmlspecialchars($category['image_url'] ?? 'https://via.placeholder.com/400'); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($category['name']); ?>">
                        </div>
                        <div class="card-body text-center">
                            <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($category['description'] ?? ''); ?></p>
                            <a href="products.php?category_id=<?php echo $category['category_id']; ?>" class="btn btn-outline-primary">
                                Shop Now
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Placeholder categories -->
                <?php
                $placeholderCategories = [
                    ['name' => 'Electronics', 'desc' => 'Latest gadgets and devices', 'img' => 'electronics'],
                    ['name' => 'Clothing', 'desc' => 'Fashion for all seasons', 'img' => 'clothing'],
                    ['name' => 'Home & Kitchen', 'desc' => 'Essential home items', 'img' => 'home'],
                    ['name' => 'Books', 'desc' => 'Best-selling books', 'img' => 'books'],
                    ['name' => 'Sports', 'desc' => 'Fitness equipment', 'img' => 'sports'],
                    ['name' => 'Beauty', 'desc' => 'Skincare products', 'img' => 'beauty']
                ];
                ?>
                <?php foreach ($placeholderCategories as $category): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card category-card h-100 border-0 shadow-sm">
                        <div class="card-img-container">
                            <img src="https://via.placeholder.com/400x300?text=<?php echo urlencode($category['name']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($category['name']); ?>">
                        </div>
                        <div class="card-body text-center">
                            <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($category['desc']); ?></p>
                            <a href="products.php?category_name=<?php echo urlencode($category['name']); ?>" class="btn btn-outline-primary">
                                Shop Now
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold">What Our Customers Say</h2>
            <p class="lead text-muted">Real reviews from satisfied customers</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="bi bi-star-fill text-warning"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="card-text">"Excellent service and fast delivery. The products are exactly as described. Highly recommend!"</p>
                        <footer class="blockquote-footer mb-0">
                            <strong>John Doe</strong>
                            <cite title="Source Title">Verified Buyer</cite>
                        </footer>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="bi bi-star-fill text-warning"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="card-text">"Great quality products at reasonable prices. Customer support is very helpful and responsive."</p>
                        <footer class="blockquote-footer mb-0">
                            <strong>Jane Smith</strong>
                            <cite title="Source Title">Verified Buyer</cite>
                        </footer>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                                <i class="bi bi-star-fill text-warning"></i>
                            <?php endfor; ?>
                            <i class="bi bi-star text-warning"></i>
                        </div>
                        <p class="card-text">"Good shopping experience. Website is easy to navigate and checkout process is smooth."</p>
                        <footer class="blockquote-footer mb-0">
                            <strong>Mike Johnson</strong>
                            <cite title="Source Title">Verified Buyer</cite>
                        </footer>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h3 class="mb-3">Subscribe to Our Newsletter</h3>
                <p>Get the latest updates on new products and exclusive offers.</p>
                <?php if (!empty($newsletter_message)): ?>
                    <div class="alert alert-light mt-3">
                        <?php echo htmlspecialchars($newsletter_message); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <?php if (isLoggedIn()): ?>
                    <form method="POST" class="d-flex">
                        <input type="email" name="newsletter_email" class="form-control me-2" placeholder="Enter your email" required>
                        <button type="submit" class="btn btn-light">Subscribe</button>
                    </form>
                <?php else: ?>
                    <div class="d-flex align-items-center gap-3">
                        <span>Please log in to subscribe.</span>
                        <a href="login.php" class="btn btn-light">Log In</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');

    addToCartButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            
            const productId = this.dataset.productId;
            const quantity = this.dataset.quantity || 1;
            const buttonIcon = this.querySelector('i');
            const originalIconClass = buttonIcon.className;

            // Add loading state to button
            this.disabled = true;
            buttonIcon.className = 'spinner-border spinner-border-sm';

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            fetch('api/cart.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(response => {
                if (response.status === 401) {
                    // User not logged in, redirect to login page
                    window.location.href = 'login.php';
                    return;
                }
                if (!response.ok) {
                    throw new Error('Network response was not ok.');
                }
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    // Optionally, update a cart counter in the header
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.cart_count;
                    }
                    // Show success feedback
                    buttonIcon.className = 'bi bi-check-lg';
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-success');
                    
                    setTimeout(() => {
                        // Restore button state
                        this.disabled = false;
                        buttonIcon.className = originalIconClass;
                        this.classList.remove('btn-success');
                        this.classList.add('btn-outline-primary');
                    }, 2000);

                } else {
                   console.error('Add to cart failed:', data.message || 'Unknown error');
                    if (window.shoppingCart && typeof window.shoppingCart.showNotification === 'function') {
                        window.shoppingCart.showNotification(data.message || 'Failed to add to cart', 'error');
                    } else {
                        alert(data.message || 'Failed to add to cart');
                    }
                    // Show error feedback (same as in catch)
                    buttonIcon.className = 'bi bi-x-lg';
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-danger');

                    setTimeout(() => {
                        // Restore button state
                        this.disabled = false;
                        buttonIcon.className = originalIconClass;
                        this.classList.remove('btn-danger');
                        this.classList.add('btn-outline-primary');
                    }, 2000);
                    return;
                }
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
                if (window.shoppingCart && typeof window.shoppingCart.showNotification === 'function') {
                    window.shoppingCart.showNotification('Failed to add to cart. Please try again.', 'error');
                } else {
                    alert('Failed to add to cart. Please try again.');
                }
                // Show error feedback
                buttonIcon.className = 'bi bi-x-lg';
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-danger');

                setTimeout(() => {
                    // Restore button state
                    this.disabled = false;
                    buttonIcon.className = originalIconClass;
                    this.classList.remove('btn-danger');
                    this.classList.add('btn-outline-primary');
                }, 2000);
            });
        });
    });
});
</script>

<style>
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
}

.hero-content {
    animation: fadeInUp 1s ease-out;
}

.hero-image {
    animation: fadeInRight 1s ease-out;
}

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
}

.product-card .card-img-container {
    aspect-ratio: 1 / 1;
}

.product-card .card-img-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card:hover .card-img-container img {
    transform: scale(1.05);
}

.category-card {
    transition: transform 0.3s ease;
}

.category-card:hover {
    transform: translateY(-5px);
}

.category-card .card-img-container img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.product-card .card-title {
    min-height: 3rem;
}

.product-card .card-text {
    min-height: 4.5rem;
}

.product-meta {
    gap: 0.75rem;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@media (max-width: 991.98px) {
    .hero-section {
        min-height: auto;
        padding: 3.5rem 0;
    }

    .hero-section .row {
        min-height: auto !important;
    }
}

@media (max-width: 767.98px) {
    .hero-content h1 {
        font-size: 2rem;
    }

    .hero-content .d-flex {
        flex-direction: column;
    }

    .hero-content .btn {
        width: 100%;
    }

    .product-card .card-title,
    .product-card .card-text {
        min-height: 0;
    }

    .product-meta {
        flex-direction: column;
        align-items: stretch !important;
    }

    .product-meta .h5 {
        margin-bottom: 0.25rem !important;
    }

    .product-cart-btn {
        width: 100%;
    }
}
</style>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
