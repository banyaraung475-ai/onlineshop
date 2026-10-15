<!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Admin Custom JS -->
        <script>
            // Mobile sidebar toggle
            document.addEventListener('DOMContentLoaded', function() {
                const sidebar = document.querySelector('.admin-sidebar');
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'btn btn-primary d-md-none position-fixed';
                toggleBtn.style.cssText = 'top: 1rem; left: 1rem; z-index: 1001;';
                toggleBtn.innerHTML = '<i class="bi bi-list"></i>';
                document.body.appendChild(toggleBtn);
                
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });

                // Edit Product Modal Handler
                const editButtons = document.querySelectorAll('.edit-product-btn');
                editButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const productId = this.getAttribute('data-product-id');
                        const name = this.getAttribute('data-name');
                        const description = this.getAttribute('data-description');
                        const price = this.getAttribute('data-price');
                        const categoryId = this.getAttribute('data-category-id');
                        const stockQuantity = this.getAttribute('data-stock-quantity');
                        const sku = this.getAttribute('data-sku');
                        const isActive = this.getAttribute('data-is-active');

                        document.getElementById('edit_product_id').value = productId;
                        document.getElementById('edit_name').value = name;
                        document.getElementById('edit_description').value = description;
                        document.getElementById('edit_price').value = price;
                        document.getElementById('edit_category_id').value = categoryId;
                        document.getElementById('edit_stock_quantity').value = stockQuantity;
                        document.getElementById('edit_sku').value = sku;
                        document.getElementById('edit_is_active').checked = isActive == '1';
                    });
                });

                // Order Search Functionality
                const orderSearchInput = document.getElementById('order-search');
                if (orderSearchInput) {
                    orderSearchInput.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase();
                        const tableRows = document.querySelectorAll('#orders-table tbody tr');
                        
                        tableRows.forEach(row => {
                            if (row.cells.length < 8) {
                                // "No orders found" row - hide during search
                                row.style.display = 'none';
                                return;
                            }
                            
                            const orderId = row.cells[0].textContent.toLowerCase();
                            const customerName = row.cells[1].textContent.toLowerCase();
                            const customerEmail = row.cells[1].querySelector('.text-muted')?.textContent.toLowerCase() || '';
                            
                            if (orderId.includes(searchTerm) || 
                                customerName.includes(searchTerm) || 
                                customerEmail.includes(searchTerm)) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        });
                    });
                }

                // Order Filters Functionality
                const applyFiltersBtn = document.querySelector('#orders-filters .btn-primary');
                if (applyFiltersBtn) {
                    applyFiltersBtn.addEventListener('click', function() {
                        const statusFilter = document.querySelector('#orders-filters select').value;
                        const dateFilter = document.querySelector('#orders-filters input[type="date"]').value;
                        const tableRows = document.querySelectorAll('#orders-table tbody tr');
                        
                        tableRows.forEach(row => {
                            let showRow = true;
                            
                            // Status filter
                            if (statusFilter !== 'All Status') {
                                const statusBadge = row.cells[5].querySelector('.badge');
                                const statusText = statusBadge ? statusBadge.textContent.toLowerCase() : '';
                                if (!statusText.includes(statusFilter.toLowerCase())) {
                                    showRow = false;
                                }
                            }
                            
                            // Date filter
                            if (dateFilter) {
                                const orderDateText = row.cells[2].textContent.trim();
                                // Parse 'M d, Y' format like 'Jan 30, 2024'
                                const dateParts = orderDateText.split(' ');
                                if (dateParts.length === 3) {
                                    const monthNames = {
                                        'Jan': 0, 'Feb': 1, 'Mar': 2, 'Apr': 3, 'May': 4, 'Jun': 5,
                                        'Jul': 6, 'Aug': 7, 'Sep': 8, 'Oct': 9, 'Nov': 10, 'Dec': 11
                                    };
                                    const month = monthNames[dateParts[0]];
                                    const day = parseInt(dateParts[1]);
                                    const year = parseInt(dateParts[2]);
                                    const orderDate = new Date(year, month, day);
                                    const filterDateObj = new Date(dateFilter);
                                    
                                    // Compare dates (ignore time)
                                    if (orderDate.toDateString() !== filterDateObj.toDateString()) {
                                        showRow = false;
                                    }
                                }
                            }
                            
                            row.style.display = showRow ? '' : 'none';
                        });
                    });
                }

                // Order Export Functionality
                const exportBtn = document.getElementById('export-orders');
                if (exportBtn) {
                    exportBtn.addEventListener('click', function() {
                        // Get visible rows (after filtering)
                        const tableRows = document.querySelectorAll('#orders-table tbody tr:not([style*="display: none"])');
                        if (tableRows.length === 0) {
                            alert('No orders to export.');
                            return;
                        }
                        
                        let csv = 'Order ID,Customer Name,Customer Email,Date,Items,Total,Status,Payment Status\n';
                        
                        tableRows.forEach(row => {
                            if (row.cells.length >= 8) {
                                const orderId = row.cells[0].textContent.replace('#', '').trim();
                                const customerName = row.cells[1].querySelector('.fw-bold')?.textContent.trim() || '';
                                const customerEmail = row.cells[1].querySelector('.text-muted')?.textContent.trim() || '';
                                const date = row.cells[2].textContent.trim();
                                const items = row.cells[3].textContent.trim();
                                const total = row.cells[4].textContent.replace('$', '').trim();
                                const status = row.cells[5].querySelector('.badge')?.textContent.trim() || '';
                                const payment = row.cells[6].querySelector('.badge')?.textContent.trim() || '';
                                
                                csv += `"${orderId}","${customerName}","${customerEmail}","${date}","${items}","${total}","${status}","${payment}"\n`;
                            }
                        });
                        
                        // Create and download CSV file
                        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                        const link = document.createElement('a');
                        const url = URL.createObjectURL(blob);
                        link.setAttribute('href', url);
                        link.setAttribute('download', 'orders_export.csv');
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    });
                }

                const viewOrderButtons = document.querySelectorAll('.view-order-btn');
                viewOrderButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const orderId = this.getAttribute('data-order-id');
                        loadOrderDetails(orderId);
                    });
                });

                const updateStatusButtons = document.querySelectorAll('.update-status');
                updateStatusButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        const orderId = this.getAttribute('data-order-id');
                        const status = this.getAttribute('data-status');
                        
                        if (confirm(`Are you sure you want to change the order status to "${status}"?`)) {
                            updateOrderStatus(orderId, status);
                        }
                    });
                });

                function loadOrderDetails(orderId) {
                    const orderDetails = document.getElementById('orderDetails');
                    orderDetails.innerHTML = `
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3 text-muted">Loading order details...</p>
                        </div>
                    `;

                    // Make AJAX call to get order details
                    fetch('api/orders.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=get&order_id=' + orderId
                    })
                    .then(response => {
                        if (response.status === 401) {
                            throw new Error('Unauthorized');
                        }
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            const order = data.order;
                            const statusBadge = getStatusBadge(order.status);
                            const paymentBadge = getPaymentBadge(order.payment_status || 'pending');
                            
                            let itemsHtml = '';
                            let subtotal = 0;
                            if (order.items && order.items.length > 0) {
                                order.items.forEach(item => {
                                    const itemTotal = item.quantity * item.unit_price;
                                    subtotal += itemTotal;
                                    itemsHtml += `
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="${item.image || 'https://via.placeholder.com/50'}" alt="${item.name}" class="rounded me-3" style="width: 50px; height: 50px;" onerror="this.src='https://via.placeholder.com/50'">
                                                    <div>
                                                        <strong>${item.name}</strong>
                                                        <br><small class="text-muted">${item.description || ''}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">${item.quantity}</td>
                                            <td class="text-end">$${parseFloat(item.unit_price).toFixed(2)}</td>
                                            <td class="text-end"><strong>$${itemTotal.toFixed(2)}</strong></td>
                                        </tr>
                                    `;
                                });
                            }
                            
                            const shippingFee = order.shipping_fee || 0;
                            const tax = (subtotal + shippingFee) * 0.08; // Assuming 8% tax
                            const total = subtotal + shippingFee + tax;
                            
                            orderDetails.innerHTML = `
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <i class="bi bi-info-circle me-2"></i>Order Information
                                                </h6>
                                                <div class="row g-3">
                                                    <div class="col-sm-6">
                                                        <small class="text-muted d-block">Order ID</small>
                                                        <strong>#${order.order_number || order.order_id}</strong>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <small class="text-muted d-block">Status</small>
                                                        ${statusBadge}
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <small class="text-muted d-block">Payment</small>
                                                        ${paymentBadge}
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <small class="text-muted d-block">Date</small>
                                                        <strong>${new Date(order.created_at).toLocaleDateString()}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <i class="bi bi-person me-2"></i>Customer Information
                                                </h6>
                                                <div class="row g-3">
                                                    <div class="col-sm-6">
                                                        <small class="text-muted d-block">Name</small>
                                                        <strong>${order.customer_name || 'N/A'}</strong>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <small class="text-muted d-block">Email</small>
                                                        <a href="mailto:${order.customer_email || ''}">${order.customer_email || 'N/A'}</a>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <small class="text-muted d-block">Phone</small>
                                                        <a href="tel:${order.customer_phone || ''}">${order.customer_phone || 'N/A'}</a>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <small class="text-muted d-block">Payment Method</small>
                                                        <strong>${order.payment_method || 'N/A'}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card border-0 bg-light mb-4">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="bi bi-geo-alt me-2"></i>Shipping Address
                                        </h6>
                                <address class="mb-0">
                                    ${order.delivery_address || order.shipping_address || 'N/A'}
                                </address>
                                    </div>
                                </div>
                                
                                <div class="card border-0">
                                    <div class="card-header bg-white">
                                        <h6 class="card-title mb-0">
                                            <i class="bi bi-cart me-2"></i>Order Items
                                        </h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Product</th>
                                                        <th class="text-center">Qty</th>
                                                        <th class="text-end">Price</th>
                                                        <th class="text-end">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${itemsHtml}
                                                </tbody>
                                                <tfoot class="table-light">
                                                    <tr>
                                                        <th colspan="3" class="text-end">Subtotal:</th>
                                                        <th class="text-end">$${subtotal.toFixed(2)}</th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="3" class="text-end">Shipping:</th>
                                                        <th class="text-end">$${shippingFee.toFixed(2)}</th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="3" class="text-end">Tax:</th>
                                                        <th class="text-end">$${tax.toFixed(2)}</th>
                                                    </tr>
                                                    <tr class="border-top">
                                                        <th colspan="3" class="text-end h5 mb-0">Total:</th>
                                                        <th class="text-end h5 mb-0 text-success">$${total.toFixed(2)}</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else {
                            orderDetails.innerHTML = `
                                <div class="text-center py-5">
                                    <div class="text-danger">
                                        <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
                                        <p class="mt-3">Failed to load order details.</p>
                                    </div>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error loading order details:', error);
                        orderDetails.innerHTML = `
                            <div class="text-center py-5">
                                <div class="text-danger">
                                    <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
                                    <p class="mt-3">Error loading order details.</p>
                                </div>
                            </div>
                        `;
                    });
                }

                function getStatusBadge(status) {
                    const badges = {
                        'pending': 'bg-info',
                        'processing': 'bg-warning',
                        'shipped': 'bg-primary',
                        'delivered': 'bg-success',
                        'cancelled': 'bg-danger'
                    };
                    return `<span class="badge ${badges[status] || 'bg-secondary'}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
                }

                function getPaymentBadge(status) {
                    const badges = {
                        'paid': 'bg-success',
                        'pending': 'bg-warning',
                        'failed': 'bg-danger',
                        'refunded': 'bg-secondary'
                    };
                    return `<span class="badge ${badges[status] || 'bg-secondary'}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
                }

                function updateOrderStatus(orderId, status) {
                    // Use AJAX to update status
                    fetch('api/orders.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=update_status&order_id=' + orderId + '&status=' + status
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update the status badge in the table
                            const row = document.querySelector(`a[data-order-id="${orderId}"][data-status="${status}"]`).closest('tr');
                            if (row) {
                                const statusCell = row.cells[5];
                                const badge = statusCell.querySelector('.badge');
                                if (badge) {
                                    badge.className = 'badge bg-' + getStatusClass(status);
                                    badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                                }
                            }
                            // Show success message
                            showAlert('Order status updated successfully.', 'success');
                        } else {
                            showAlert('Failed to update order status: ' + (data.message || 'Unknown error'), 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error updating order status:', error);
                        showAlert('Error updating order status.', 'danger');
                    });
                }

                function getStatusClass(status) {
                    const classes = {
                        'pending': 'info',
                        'processing': 'warning',
                        'shipped': 'primary',
                        'delivered': 'success',
                        'cancelled': 'danger'
                    };
                    return classes[status] || 'secondary';
                }

                function showAlert(message, type) {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
                    alertDiv.innerHTML = `
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    const container = document.querySelector('.admin-main .d-flex.justify-content-between.align-items-center.mb-4');
                    container.insertAdjacentElement('afterend', alertDiv);
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 5000);
                }
            });
        </script>
    </body>
</html>
