// Real-time cart functionality
class ShoppingCart {
    constructor() {
        this.cartCountEl = document.querySelector('.cart-count');
        this.cartTotalEl = document.querySelector('.cart-total');
        this.init();
    }

    init() {
        this.updateCartDisplay();
        this.bindEvents();
    }

    bindEvents() {
        // Add to cart buttons
        document.addEventListener('click', (e) => {
            const button = e.target.closest('.add-to-cart-btn');
            if (button) {
                const productId = button.dataset.productId;
                if (!productId) {
                    console.warn('Add to cart skipped: missing product id');
                    return;
                }
                const quantity = Number.parseInt(button.dataset.quantity, 10) || 1;
                this.addToCart(productId, quantity);
            }
        });

        // Cart item quantity updates
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('cart-quantity')) {
                const productId = e.target.dataset.productId;
                const quantity = Number.parseInt(e.target.value, 10);
                this.updateCartItem(productId, quantity);
            }
        });

        // Remove from cart
        document.addEventListener('click', (e) => {
            const button = e.target.closest('.remove-from-cart');
            if (button) {
                const productId = button.dataset.productId;
                this.removeFromCart(productId);
            }
        });
    }

    async addToCart(productId, quantity = 1) {
        try {
            const response = await fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'add',
                    product_id: productId,
                    quantity: String(quantity)
                }).toString()
            });
            
            if (response.status === 401) {
                window.location.href = 'login.php';
                return;
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(result.message, 'success');
                this.updateCartDisplay();
                this.updateCartSidebar();
            } else {
                // Suppress alert for failed add-to-cart
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            // Suppress alert for failed add-to-cart
        }
    }

    async updateCartItem(productId, quantity) {
        if (!Number.isFinite(quantity) || quantity < 1) {
            return;
        }
        try {
            const response = await fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'update',
                    product_id: productId,
                    quantity: String(quantity)
                }).toString()
            });

            const result = await response.json();
            
            if (result.success) {
                this.updateCartDisplay();
                this.updateCartSidebar();
                
                // Update item total in cart page
                const itemTotalEl = document.querySelector(`.cart-item-total[data-product-id="${productId}"]`);
                if (itemTotalEl) {
                    // Calculate new total based on product price and quantity
                    const productPrice = Number.parseFloat(itemTotalEl.dataset.price);
                    if (Number.isFinite(productPrice)) {
                        const newTotal = productPrice * quantity;
                        itemTotalEl.textContent = `Ks${newTotal.toLocaleString()}`;
                    }
                }
            }
        } catch (error) {
            console.error('Error updating cart:', error);
        }
    }

    async removeFromCart(productId) {
        if (!confirm('Are you sure you want to remove this item from cart?')) {
            return;
        }

        try {
            const response = await fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'remove',
                    product_id: productId
                }).toString()
            });

            const result = await response.json();
            
            if (result.success) {
                this.showNotification(result.message, 'success');
                this.updateCartDisplay();
                this.updateCartSidebar();
                
                // Remove item from cart page
                const itemRow = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
                if (itemRow) {
                    itemRow.remove();
                }
            }
        } catch (error) {
            console.error('Error removing from cart:', error);
        }
    }

    async updateCartDisplay() {
        try {
            const response = await fetch('api/cart.php?action=get');
            if (response.status === 401) {
                return;
            }
            const result = await response.json();
            
            if (result.success) {
                if (this.cartCountEl) {
                    this.cartCountEl.textContent = result.cart_count;
                }
                if (this.cartTotalEl) {
                    this.cartTotalEl.textContent = `Ks${result.cart_total.toLocaleString()}`;
                }
            }
        } catch (error) {
            console.error('Error updating cart display:', error);
        }
    }

    async updateCartSidebar() {
        // Update cart sidebar/modal if exists
        const cartSidebar = document.querySelector('.cart-sidebar');
        if (cartSidebar) {
            // This would update the cart sidebar content
            // Implementation depends on your cart sidebar structure
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// Real-time order tracking
class OrderTracker {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.startPolling();
    }

    bindEvents() {
        // Track order button
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('track-order-btn')) {
                const orderId = e.target.dataset.orderId;
                this.showTrackingModal(orderId);
            }
        });
    }

    startPolling() {
        // Poll for order updates every 30 seconds
        setInterval(async () => {
            if (document.hidden) return; // Don't poll when tab is hidden
            
            const orderStatusEls = document.querySelectorAll('.order-status');
            for (const el of orderStatusEls) {
                const orderId = el.dataset.orderId;
                await this.updateOrderStatus(orderId);
            }
        }, 30000);
    }

    async updateOrderStatus(orderId) {
        try {
            const response = await fetch(`api/orders.php?action=get&order_id=${orderId}`);
            const result = await response.json();
            
            if (result.success) {
                const statusEl = document.querySelector(`.order-status[data-order-id="${orderId}"]`);
                if (statusEl) {
                    statusEl.textContent = result.order.status;
                    statusEl.className = `badge bg-${this.getStatusColor(result.order.status)}`;
                }
            }
        } catch (error) {
            console.error('Error updating order status:', error);
        }
    }

    getStatusColor(status) {
        switch (status) {
            case 'pending': return 'warning';
            case 'processing': return 'info';
            case 'shipped': return 'primary';
            case 'delivered': return 'success';
            case 'cancelled': return 'danger';
            default: return 'secondary';
        }
    }

    async showTrackingModal(orderId) {
        try {
            const response = await fetch(`api/orders.php?action=track&order_id=${orderId}`);
            const result = await response.json();
            
            if (result.success) {
                // Show tracking modal
                const modal = new bootstrap.Modal(document.getElementById('trackingModal'));
                // Update modal content with tracking info
                modal.show();
            }
        } catch (error) {
            console.error('Error fetching tracking info:', error);
        }
    }
}

// Real-time notifications system
class NotificationSystem {
    constructor() {
        this.notificationBadgeEl = document.querySelector('.notification-badge');
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateNotificationBadge();
        this.startPolling();
    }

    bindEvents() {
        // Notification dropdown
        const notificationBtn = document.querySelector('.notification-btn');
        if (notificationBtn) {
            notificationBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.renderNotificationState('Loading notifications...');
                this.loadNotifications();
            });
        }

        // Mark all as read
        const markAllReadBtn = document.querySelector('.mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => {
                this.markAllAsRead();
            });
        }
    }

    startPolling() {
        // Poll for new notifications every 30 seconds
        setInterval(async () => {
            if (document.hidden) return; // Don't poll when tab is hidden
            
            await this.checkForNewNotifications();
        }, 30000);
    }

    async checkForNewNotifications() {
        try {
            const response = await fetch('api/notifications.php?action=get_latest');
            if (!response.ok) {
                return;
            }
            const result = await response.json();
            
            if (result.success && result.notification) {
                const notification = result.notification;
                // Check if this is a new notification (not already shown)
                if (!this.isNotificationShown(notification.notification_id)) {
                    this.showDesktopNotification(notification);
                    this.addToNotificationList(notification);
                    this.updateNotificationBadge();
                }
            }
        } catch (error) {
            console.error('Error checking notifications:', error);
        }
    }

    async loadNotifications() {
        try {
            const response = await fetch('api/notifications.php?action=get_unread');
            if (!response.ok) {
                this.renderNotificationState('Failed to load notifications.');
                return;
            }
            const result = await response.json();
            
            if (result.success) {
                this.renderNotifications(result.notifications);
                this.updateNotificationBadge(result.count);
            } else {
                this.renderNotificationState(result.message || 'Failed to load notifications.');
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            this.renderNotificationState('Failed to load notifications.');
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('api/notifications.php?action=mark_all_read', {
                method: 'POST'
            });
            if (!response.ok) {
                return;
            }
            const result = await response.json();
            
            if (result.success) {
                this.clearNotificationList();
                this.renderNotificationState('No new notifications.');
                this.updateNotificationBadge(0);
            }
        } catch (error) {
            console.error('Error marking notifications as read:', error);
        }
    }

    showDesktopNotification(notification) {
        if ('Notification' in window && Notification.permission === 'granted') {
            const desktopNotification = new Notification(notification.title, {
                body: notification.message,
                icon: '/assets/images/logo.png',
                tag: notification.notification_id
            });

            desktopNotification.onclick = () => {
                window.focus();
                this.markAsRead(notification.notification_id);
                desktopNotification.close();
            };

            setTimeout(() => {
                desktopNotification.close();
            }, 5000);
        } else if ('Notification' in window && Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    this.showDesktopNotification(notification);
                }
            });
        }

        // Also show in-app notification
        this.showInAppNotification(notification);
    }

    showInAppNotification(notification) {
        if (window.shoppingCart && typeof window.shoppingCart.showNotification === 'function') {
            window.shoppingCart.showNotification(notification.message, notification.type || 'info');
            return;
        }

        // Fallback toast when cart module is unavailable.
        const toast = document.createElement('div');
        toast.className = 'alert alert-info alert-dismissible fade show position-fixed';
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${notification.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    addToNotificationList(notification) {
        const list = document.querySelector('.notification-list');
        if (list) {
            const item = document.createElement('li');
            item.className = `notification-item ${notification.is_read ? 'read' : 'unread'}`;
            item.dataset.id = notification.notification_id;
            item.innerHTML = `
                <div class="notification-content">
                    <strong>${notification.title}</strong>
                    <p>${notification.message}</p>
                    <small>${new Date(notification.created_at).toLocaleString()}</small>
                </div>
                <button class="btn btn-sm btn-outline-primary mark-read" data-id="${notification.notification_id}">
                    Mark Read
                </button>
            `;
            
            list.insertBefore(item, list.firstChild);
            
            // Add event listener for mark as read
            item.querySelector('.mark-read').addEventListener('click', () => {
                this.markAsRead(notification.notification_id);
                item.remove();
                this.updateNotificationBadge();
            });
        }
    }

    async markAsRead(notificationId) {
        if (!notificationId) {
            return;
        }
        try {
            const response = await fetch('api/notifications.php?action=mark_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    notification_id: notificationId
                }).toString()
            });
            if (!response.ok) {
                return;
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    updateNotificationBadge(count = null) {
        const badge = this.notificationBadgeEl;
        if (badge) {
            if (count !== null) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'inline' : 'none';
            } else {
                // Fetch current count
                this.getUnreadCount().then(currentCount => {
                    badge.textContent = currentCount;
                    badge.style.display = currentCount > 0 ? 'inline' : 'none';
                });
            }
        }
    }

    async getUnreadCount() {
        try {
            const response = await fetch('api/notifications.php?action=get_unread');
            if (!response.ok) {
                return 0;
            }
            const result = await response.json();
            return result.success ? result.count : 0;
        } catch (error) {
            console.error('Error getting unread count:', error);
            return 0;
        }
    }

    isNotificationShown(notificationId) {
        return document.querySelector(`.notification-item[data-id="${notificationId}"]`) !== null;
    }

    renderNotifications(notifications) {
        const list = document.querySelector('.notification-list');
        if (list) {
            list.innerHTML = '';
            const items = Array.isArray(notifications) ? notifications : [];
            if (items.length === 0) {
                this.renderNotificationState('No new notifications.');
                return;
            }

            items.forEach(notification => {
                this.addToNotificationList(notification);
            });
        }
    }

    clearNotificationList() {
        const list = document.querySelector('.notification-list');
        if (list) {
            list.innerHTML = '';
        }
    }

    renderNotificationState(message) {
        const list = document.querySelector('.notification-list');
        if (!list) {
            return;
        }

        list.innerHTML = `<li class="notification-state">${message}</li>`;
    }
}

// Initialize real-time features
document.addEventListener('DOMContentLoaded', function() {
    // Initialize shopping cart
    const shoppingCart = new ShoppingCart();
    window.shoppingCart = shoppingCart;
    
    // Initialize order tracking
    const orderTracker = new OrderTracker();
    
    // Initialize notifications (only if user is logged in)
    if (document.body.classList.contains('user-logged-in')) {
        const notificationSystem = new NotificationSystem();
    }
    
    // Update delivery fee on checkout page
    const deliveryMethod = document.getElementById('delivery_method');
    if (deliveryMethod) {
        deliveryMethod.addEventListener('change', function() {
            const deliveryFee = this.value === 'Express' ? 5000 : (this.value === 'Pickup' ? 0 : 2000);
            document.getElementById('delivery-fee').textContent = 'Ks' + deliveryFee.toLocaleString();
            
            const subtotal = Number.parseFloat(document.getElementById('subtotal').dataset.value);
            document.getElementById('order-total').textContent = 'Ks' + (subtotal + deliveryFee).toLocaleString('en-US', {minimumFractionDigits: 2});
        });
    }
    
    // Initialize tooltips
    const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach((tooltipTriggerEl) => {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Search functionality
    const searchForm = document.querySelector('.search-pill form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const query = this.querySelector('input[name="search"]').value.trim();
            if (query.length < 2) {
                e.preventDefault();
                shoppingCart.showNotification('Please enter at least 2 characters to search', 'error');
            }
        });
    }
});
