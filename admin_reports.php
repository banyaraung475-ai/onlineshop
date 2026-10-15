<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';

// Only allow admin users
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$page_title = "Reports & Analytics";
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
            <a href="admin_reviews.php" class="admin-nav-link">
                <i class="bi bi-chat-left-text me-2"></i> Reviews
            </a>
            <a href="admin_reports.php" class="admin-nav-link active">
                <i class="bi bi-graph-up me-2"></i> Reports
            </a>
        </nav>
    </div>
</div>

<div class="admin-main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Reports & Analytics</h1>
        <div>
            <button class="btn btn-outline-primary">
                <i class="bi bi-download me-2"></i>Export PDF
            </button>
            <button class="btn btn-primary ms-2">
                <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
            </button>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" value="2024-01-01">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" value="2024-01-31">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Report Type</label>
                    <select class="form-select">
                        <option selected>Sales Report</option>
                        <option>Inventory Report</option>
                        <option>Customer Report</option>
                        <option>Financial Report</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100">Generate Report</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number">$45,678</div>
                <div class="stat-label">Total Revenue</div>
                <div class="mt-2">
                    <span class="text-success small">
                        <i class="bi bi-arrow-up"></i> 23% from last month
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number">1,234</div>
                <div class="stat-label">Total Orders</div>
                <div class="mt-2">
                    <span class="text-success small">
                        <i class="bi bi-arrow-up"></i> 12% from last month
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number">$37.02</div>
                <div class="stat-label">Average Order Value</div>
                <div class="mt-2">
                    <span class="text-success small">
                        <i class="bi bi-arrow-up"></i> 8% from last month
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number">89</div>
                <div class="stat-label">Conversion Rate (%)</div>
                <div class="mt-2">
                    <span class="text-danger small">
                        <i class="bi bi-arrow-down"></i> 2% from last month
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Chart -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Sales Trend</h5>
        </div>
        <div class="card-body">
            <canvas id="salesChart" height="100"></canvas>
        </div>
    </div>

    <!-- Top Products -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Selling Products</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Sales</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Wireless Headphones</td>
                                    <td>145</td>
                                    <td>$12,905</td>
                                </tr>
                                <tr>
                                    <td>Cotton T-Shirt</td>
                                    <td>234</td>
                                    <td>$5,846</td>
                                </tr>
                                <tr>
                                    <td>Coffee Maker</td>
                                    <td>89</td>
                                    <td>$13,349</td>
                                </tr>
                                <tr>
                                    <td>Running Shoes</td>
                                    <td>167</td>
                                    <td>$25,050</td>
                                </tr>
                                <tr>
                                    <td>Backpack</td>
                                    <td>98</td>
                                    <td>$4,900</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Sales by Category</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: ['Jan 1', 'Jan 7', 'Jan 14', 'Jan 21', 'Jan 28'],
            datasets: [{
                label: 'Sales',
                data: [12000, 19000, 15000, 25000, 22000],
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Category Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: ['Electronics', 'Clothing', 'Home', 'Books', 'Sports'],
            datasets: [{
                data: [35, 25, 20, 12, 8],
                backgroundColor: [
                    '#6366f1',
                    '#8b5cf6',
                    '#ec4899',
                    '#f59e0b',
                    '#10b981'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
