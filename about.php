<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';

$db = new Database();
$page_title = "About Us";
include_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <!-- Hero Section -->
    <section class="text-center py-5 mb-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="display-4 fw-bold mb-4">About <?php echo SITE_NAME; ?></h1>
                <p class="lead text-muted">Your trusted online shopping destination for quality products at unbeatable prices</p>
            </div>
        </div>
    </section>

    <!-- Our Story -->
    <section class="mb-5">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2 class="mb-4">Our Story</h2>
                <p class="mb-3">Founded in 2020, <?php echo SITE_NAME; ?> started with a simple mission: to make quality products accessible to everyone in Myanmar. What began as a small online store has grown into a comprehensive ecommerce platform serving thousands of customers nationwide.</p>
                <p class="mb-3">We believe that online shopping should be convenient, affordable, and trustworthy. That's why we've carefully curated our product selection, partnered with reliable suppliers, and built a platform that puts our customers first.</p>
                <p>Today, we're proud to be one of Myanmar's fastest-growing online retailers, offering everything from electronics to groceries, all with the same commitment to quality and service that started our journey.</p>
            </div>
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1560472354-b33ff0c44a43?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80" 
                     alt="Our Team" class="img-fluid rounded shadow">
            </div>
        </div>
    </section>

    <!-- Mission & Values -->
    <section class="mb-5">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="bi bi-bullseye text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="card-title">Our Mission</h4>
                        <p class="card-text">To provide exceptional shopping experiences by offering quality products, competitive prices, and outstanding customer service to every customer in Myanmar.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="bi bi-eye text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="card-title">Our Vision</h4>
                        <p class="card-text">To become Myanmar's most trusted and customer-centric online shopping destination, known for reliability, innovation, and exceptional service.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="bi bi-heart text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="card-title">Our Values</h4>
                        <p class="card-text">Integrity, customer focus, innovation, quality, and sustainability guide everything we do, from product selection to customer service.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics -->
    <section class="mb-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-5">
                <h3 class="text-center mb-5">Our Impact</h3>
                <div class="row text-center">
                    <div class="col-md-3 mb-4">
                        <div class="stat-number">50,000+</div>
                        <div class="stat-label">Happy Customers</div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-number">5,000+</div>
                        <div class="stat-label">Products Available</div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-number">100+</div>
                        <div class="stat-label">Partner Brands</div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-number">4.8★</div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="mb-5">
        <div class="text-center mb-5">
            <h3 class="mb-3">Meet Our Team</h3>
            <p class="text-muted">The passionate people behind <?php echo SITE_NAME; ?></p>
        </div>
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Team Member" class="rounded-circle mb-3" style="width: 120px; height: 120px;">
                        <h5 class="card-title">Aung Min</h5>
                        <p class="text-muted">Founder & CEO</p>
                        <p class="small">Visionary leader with 15+ years in ecommerce and retail</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="#" class="text-primary"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="text-primary"><i class="bi bi-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <img src="https://randomuser.me/api/portraits/women/43.jpg" alt="Team Member" class="rounded-circle mb-3" style="width: 120px; height: 120px;">
                        <h5 class="card-title">Thiri Htun</h5>
                        <p class="text-muted">Operations Manager</p>
                        <p class="small">Ensuring smooth operations and exceptional customer service</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="#" class="text-primary"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="text-primary"><i class="bi bi-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <img src="https://randomuser.me/api/portraits/men/65.jpg" alt="Team Member" class="rounded-circle mb-3" style="width: 120px; height: 120px;">
                        <h5 class="card-title">Zaw Myo</h5>
                        <p class="text-muted">Tech Lead</p>
                        <p class="small">Building and maintaining our cutting-edge platform</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="#" class="text-primary"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="text-primary"><i class="bi bi-github"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <img src="https://randomuser.me/api/portraits/women/65.jpg" alt="Team Member" class="rounded-circle mb-3" style="width: 120px; height: 120px;">
                        <h5 class="card-title">May Su</h5>
                        <p class="text-muted">Marketing Head</p>
                        <p class="small">Creating amazing experiences and growing our community</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="#" class="text-primary"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="text-primary"><i class="bi bi-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Partners -->
    <section class="mb-5">
        <div class="text-center mb-5">
            <h3 class="mb-3">Our Trusted Partners</h3>
            <p class="text-muted">Brands that trust us to deliver their products to customers</p>
        </div>
        <div class="row align-items-center">
            <div class="col-md-2 col-4 mb-4">
                <div class="text-center">
                    <div class="bg-light rounded p-3">
                        <i class="bi bi-cpu text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <p class="small mt-2 mb-0">TechCorp</p>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-4">
                <div class="text-center">
                    <div class="bg-light rounded p-3">
                        <i class="bi bi-bag text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <p class="small mt-2 mb-0">FashionHub</p>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-4">
                <div class="text-center">
                    <div class="bg-light rounded p-3">
                        <i class="bi bi-house text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <p class="small mt-2 mb-0">HomeStyle</p>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-4">
                <div class="text-center">
                    <div class="bg-light rounded p-3">
                        <i class="bi bi-book text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <p class="small mt-2 mb-0">BookWorld</p>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-4">
                <div class="text-center">
                    <div class="bg-light rounded p-3">
                        <i class="bi bi-heart-pulse text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <p class="small mt-2 mb-0">HealthPlus</p>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-4">
                <div class="text-center">
                    <div class="bg-light rounded p-3">
                        <i class="bi bi-controller text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <p class="small mt-2 mb-0">GameZone</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="mb-5">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-center p-5">
                <h3 class="text-white mb-3">Ready to Start Shopping?</h3>
                <p class="text-white mb-4">Join thousands of satisfied customers and discover amazing products at unbeatable prices</p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="products.php" class="btn btn-light btn-lg">Browse Products</a>
                    <a href="register.php" class="btn btn-outline-light btn-lg">Create Account</a>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
