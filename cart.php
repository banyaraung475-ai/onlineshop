<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/classes/Cart.php';
require_once __DIR__ . '/classes/Product.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php?redirect=cart.php');
}

$db = new Database();
$user_id = $_SESSION['user_id'] ?? null;
$cart = new Cart($db, $user_id);
$product = new Product($db);

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);
    
    switch ($action) {
        case 'update':
            $quantity = (int)$_POST['quantity'];
            if ($cart->updateItem($product_id, $quantity)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Cart updated successfully'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to update cart item'];
            }
            break;
            
        case 'remove':
            if ($cart->removeItem($product_id)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Item removed from cart'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to remove item'];
            }
            break;
            
        case 'clear':
            $cart->clearCart();
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Cart cleared'];
            break;
    }
    
    redirect('cart.php');
}

$cart_items = $cart->getItems();
$cart_count = $cart->getItemCount();
$cart_total = $cart->getTotal();

$page_title = "Shopping Cart";
include_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Shopping Cart</li>
    </ol>
</nav>

<!-- Cart Content -->
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Shopping Cart</h4>
                <span class="badge bg-light text-dark ms-2"><?php echo $cart_count; ?> items</span>
            </div>
            
            <?php if (!empty($cart_items)): ?>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <?php
                                    // Get product details for image and stock
                                    $product_details = $product->getProductById($item['id']);
                                    $available_stock = $product_details['stock_quantity'] ?? 0;
                                    ?>
                                    <tr class="cart-item" data-product-id="<?php echo $item['id']; ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo htmlspecialchars($item['image'] ?? 'https://via.placeholder.com/80'); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                     class="img-thumbnail me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    <?php if ($available_stock < $item['quantity']): ?>
                                                        <small class="text-warning">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                            Only <?php echo $available_stock; ?> in stock
                                                        </small>
                                                    <?php elseif ($available_stock < 5): ?>
                                                        <small class="text-info">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            Low stock: <?php echo $available_stock; ?> remaining
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="h6"><?php echo formatPrice($item['price']); ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <button class="btn btn-outline-secondary btn-sm quantity-btn" 
                                                        data-action="decrease" 
                                                        data-product-id="<?php echo $item['id']; ?>"
                                                        <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" 
                                                       class="form-control cart-quantity mx-2 text-center" 
                                                       style="width: 80px;" 
                                                       value="<?php echo $item['quantity']; ?>"
                                                       min="1"
                                                       max="<?php echo $available_stock; ?>"
                                                       data-product-id="<?php echo $item['id']; ?>"
                                                       data-price="<?php echo $item['price']; ?>">
                                                <button class="btn btn-outline-secondary btn-sm quantity-btn" 
                                                        data-action="increase" 
                                                        data-product-id="<?php echo $item['id']; ?>"
                                                        <?php echo $item['quantity'] >= $available_stock ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="h6 cart-item-total" 
                                                  data-product-id="<?php echo $item['id']; ?>"
                                                  data-price="<?php echo $item['price']; ?>">
                                                <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-danger btn-sm remove-from-cart" 
                                                    data-product-id="<?php echo $item['id']; ?>"
                                                    title="Remove item">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="card-body text-center py-5">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Your cart is empty</h5>
                    <p class="text-muted">Add some products to get started</p>
                    <a href="products.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>Shop Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <span class="fw-bold" id="cart-subtotal"><?php echo formatPrice($cart_total); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Shipping</span>
                    <span class="fw-bold" id="cart-shipping">Free</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Tax (5%)</span>
                    <span class="fw-bold" id="cart-tax"><?php echo formatPrice($cart_total * 0.05); ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <span class="h5">Total</span>
                    <span class="h5 fw-bold" id="cart-total"><?php echo formatPrice($cart_total + ($cart_total * 0.05)); ?></span>
                </div>
                
                <div class="d-grid gap-2">
                    <?php if (!empty($cart_items)): ?>
                        <a href="checkout.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-check-circle me-2"></i>Proceed to Checkout
                        </a>
                        <button type="button" class="btn btn-outline-danger clear-cart-btn">
                            <i class="fas fa-broom me-2"></i>Clear Cart
                        </button>
                    <?php endif; ?>
                    <a href="products.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                    </a>
                </div>
                
                <!-- Security badges -->
                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-lock me-1"></i>
                        Secure checkout | <i class="fas fa-shield-alt me-1"></i>
                        100% Safe
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Delivery Info -->
        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <h6 class="card-title"><i class="fas fa-truck me-2"></i>Delivery Options</h6>
                <ul class="list-unstyled mb-0">
                    <li class="small text-muted mb-1">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        Standard: 3-5 business days (Free)
                    </li>
                    <li class="small text-muted mb-1">
                        <i class="fas fa-check-circle text-info me-2"></i>
                        Express: 1-2 business days (Ks 5,000)
                    </li>
                    <li class="small text-muted">
                        <i class="fas fa-check-circle text-warning me-2"></i>
                        Pickup: Store pickup (Free)
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Empty Cart Modal -->
<div class="modal fade" id="emptyCartModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clear Cart</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to clear your cart? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmClearCart">Clear Cart</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function submitClearCart() {
        fetch('api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({ action: 'clear' }).toString()
        }).then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                const message = data.message || 'Failed to clear cart';
                throw new Error(message);
            }
            return data;
        }).then(() => {
            location.reload();
        }).catch((error) => {
            console.error('Clear cart error:', error);
            alert(error.message || 'Failed to clear cart');
        });
    }

    // Quantity buttons
    document.querySelectorAll('.quantity-btn').forEach((btn) => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const action = this.dataset.action;
            const input = document.querySelector(`.cart-quantity[data-product-id="${productId}"]`);
            if (!input) return;
            const currentQuantity = Number.parseInt(input.value, 10);
            
            if (action === 'increase') {
                input.value = currentQuantity + 1;
            } else if (action === 'decrease' && currentQuantity > 1) {
                input.value = currentQuantity - 1;
            }
            
            // Trigger change event
            input.dispatchEvent(new Event('change'));
        });
    });
    
    // Clear cart button
    const clearCartBtn = document.querySelector('.clear-cart-btn');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', function() {
            if (window.bootstrap && bootstrap.Modal) {
                const modal = new bootstrap.Modal(document.getElementById('emptyCartModal'));
                modal.show();
            } else {
                const confirmed = window.confirm('Are you sure you want to clear your cart?');
                if (confirmed) {
                    submitClearCart();
                }
            }
        });
    }

    const confirmClearCartBtn = document.getElementById('confirmClearCart');
    if (confirmClearCartBtn) {
        confirmClearCartBtn.addEventListener('click', function() {
            submitClearCart();
        });
    }

    // Remove item buttons
    document.querySelectorAll('.remove-from-cart').forEach((btn) => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            if (!productId) return;

            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({ action: 'remove', product_id: productId }).toString()
            }).then(response => response.json())
              .then(result => {
                  if (result.success) {
                      location.reload();
                  }
              });
        });
    });
    
    // Update cart display when items change
    window.addEventListener('cartUpdated', function() {
        // This will be triggered by the real-time cart system
        location.reload();
    });
});
</script>

<style>
.cart-item {
    transition: all 0.3s ease;
}

.cart-item:hover {
    background-color: #f8f9fa;
}

.quantity-btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cart-quantity {
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.cart-quantity:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.remove-from-cart {
    transition: all 0.3s ease;
}

.remove-from-cart:hover {
    transform: scale(1.1);
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    border-bottom: 1px solid #dee2e6;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
    
