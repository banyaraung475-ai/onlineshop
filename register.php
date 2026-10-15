<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput(trim($_POST['username']));
    $email = sanitizeInput(trim($_POST['email']));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $full_name = sanitizeInput(trim($_POST['full_name'] ?? ''));
    $address = sanitizeInput(trim($_POST['address'] ?? ''));
    $phone_raw = trim($_POST['phone'] ?? '');
    $phone = sanitizeInput($phone_raw);

    if ($phone_raw !== '') {
        $normalized_phone = preg_replace('/[\s\-\(\)]/', '', $phone_raw);

        // Accept +959xxxxxxxxx / 959xxxxxxxxx and normalize to 09xxxxxxxxx
        if (strpos($normalized_phone, '+959') === 0) {
            $normalized_phone = '0' . substr($normalized_phone, 4);
        } elseif (strpos($normalized_phone, '959') === 0) {
            $normalized_phone = '0' . substr($normalized_phone, 3);
        }

        if (!preg_match('/^09\d{9}$/', $normalized_phone)) {
            $error = "Please enter a valid phone number (must start with 09 and be 11 digits).";
        } else {
            $phone = sanitizeInput($normalized_phone);
        }
    }
    
    if (empty($error) && (empty($username) || empty($email) || empty($password))) {
        $error = "Please fill in all required fields.";
    } elseif (empty($error) && !validateEmail($email)) {
        $error = "Please enter a valid email address.";
    } elseif (empty($error) && strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (empty($error) && $password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $result = $auth->register([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'full_name' => $full_name,
            'address' => $address,
            'phone' => $phone
        ]);

        if (!empty($result['success'])) {
            redirect('login.php?registered=1');
        } else {
            $error = $result['message'] ?? "Registration failed. Username or email may already exist.";
        }
    }
}

$page_title = "Register";
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="mb-3">Create Account</h2>
                        <p class="text-muted">Join <?php echo SITE_NAME; ?> for exclusive deals and offers</p>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           placeholder="Choose a username" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="your@email.com" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Create a password" required minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock-fill"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           placeholder="Confirm your password" required minlength="8">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person-badge"></i>
                                    </span>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           placeholder="Your full name">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-telephone"></i>
                                    </span>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           placeholder="09xxxxxxxxx" inputmode="numeric"
                                           pattern="^09[0-9]{9}$"
                                           maxlength="11"
                                           title="Phone number must start with 09 and contain exactly 11 digits.">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Delivery Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-geo-alt"></i>
                                </span>
                                <textarea class="form-control" id="address" name="address" rows="2" 
                                          placeholder="Your delivery address"></textarea>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> 
                                    and <a href="#" class="text-decoration-none">Privacy Policy</a>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="bi bi-person-plus me-2"></i>Create Account
                        </button>
                        
                        <div class="text-center">
                            <p class="mb-0">Already have an account? 
                                <a href="login.php" class="text-decoration-none">Sign in</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        const icon = this.querySelector('i');
        icon.classList.toggle('bi-eye');
        icon.classList.toggle('bi-eye-slash');
    });
    
    // Password confirmation validation
    const confirmPassword = document.getElementById('confirm_password');
    confirmPassword.addEventListener('input', function() {
        if (this.value !== passwordInput.value) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Auto-focus on username field
    document.getElementById('username').focus();
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
