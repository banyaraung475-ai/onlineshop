        </main>
        
        <footer class="bg-dark text-white py-4 mt-5">
            <div class="container">
                <div class="row">
                    <div class="col-md-4">
                        <h5><?php echo SITE_NAME; ?></h5>
                        <p>Your one-stop shop for all your needs.</p>
                    </div>
                    <div class="col-md-4">
                        <h5>Quick Links</h5>
                        <ul class="list-unstyled">
                            <li><a href="index.php" class="text-white">Home</a></li>
                            <li><a href="products.php" class="text-white">Products</a></li>
                            <li><a href="contact.php" class="text-white">My Order</a></li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h5>Payment Methods</h5>
                        <img src="assets/images/kpaylogo.png" alt="KPay" height="30" class="me-2">
                        <img src="assets/images/Ayalogo.png" alt="AyaPay" height="30">
                    </div>
                </div>
                <div class="text-center mt-3">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                </div>
            </div>
        </footer>
        
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Custom JS -->
        <script src="assets/js/script.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/script.js'); ?>"></script>
    </body>
</html>
