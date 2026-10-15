<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/Database.php";
require_once __DIR__ . "/../classes/Cart.php";
require_once __DIR__ . "/functions.php";
require_once __DIR__ . "/auth.php";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " | " : ""; ?><?php echo SITE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .notification-dropdown {
            width: 320px;
            padding: 0.25rem 0;
        }

        .notification-dropdown .dropdown-header {
            padding: 0.75rem 1rem 0.5rem;
            font-weight: 600;
        }

        .notification-dropdown .dropdown-divider {
            margin: 0.35rem 0;
        }

        .notification-dropdown .notification-list {
            list-style: none;
            max-height: 320px;
            overflow-y: auto;
            padding: 0;
            margin: 0;
        }

        .notification-dropdown .notification-list .notification-item {
            padding: 0.6rem 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .notification-dropdown .notification-list .notification-item:last-child {
            border-bottom: none;
        }

        .notification-dropdown .notification-state {
            padding: 0.9rem 1rem;
            color: #6c757d;
            font-size: 0.92rem;
        }

        .notification-dropdown .dropdown-item {
            white-space: normal;
        }

        .notification-dropdown .text-center {
            padding: 0.5rem 1rem 0.75rem;
        }
    </style>
</head>
<body class="<?php echo isLoggedIn() ? 'user-logged-in' : 'user-guest'; ?>">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <img class="brand-mark" src="https://img.freepik.com/premium-vector/online-shop-logo-with-modern-concept-business-premium-vector_561067-126.jpg" alt="Brand Mark" width="40" height="40">
                <span class="brand-text"><?php echo SITE_NAME; ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                </ul>
                <form class="d-none d-lg-flex align-items-center search-pill me-3" action="products.php" method="get">
                    <i class="bi bi-search"></i>
                    <input class="form-control form-control-sm border-0" type="search" name="search" placeholder="Search 5,000+ products">
                </form>
                <ul class="navbar-nav align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <span class="cart-count badge bg-primary ms-1"><?php echo getCartCount(); ?></span>
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link notification-btn dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span class="notification-badge badge bg-danger ms-1" style="display: none;">0</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end notification-dropdown">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <ul class="notification-list">
                                        <li class="notification-state">Loading notifications...</li>
                                    </ul>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li class="text-center">
                                    <button class="btn btn-sm btn-outline-primary mark-all-read">Mark All Read</button>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo $_SESSION["username"]; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="my-account.php">Account</a></li>
                                <li><a class="dropdown-item" href="my-orders.php">My Orders</a></li>
                                <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="admin.php">Admin Dashboard</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <main class="container my-4">
