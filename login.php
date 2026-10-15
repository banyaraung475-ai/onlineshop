<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['registered']) && $_GET['registered'] === '1') {
    $success = "Registration successful. Please sign in to continue.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput(trim($_POST['username']));
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        if (login($username, $password)) {
            $redirect = $_GET['redirect'] ?? 'index.php';
            redirect($redirect);
        } else {
            $error = "Invalid username or password.";
        }
    }
}

$page_title = "Login";
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="mb-3">Welcome Back</h2>
                        <p class="text-muted">Sign in to your <?php echo SITE_NAME; ?> account</p>
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
                        <div class="mb-3">
                            <label for="username" class="form-label">Username or Email</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Enter your username or email" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                            <a href="forgot-password.php" class="text-decoration-none">Forgot password?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                        </button>
                        
                        <div class="text-center">
                            <p class="mb-0">Don't have an account? 
                                <a href="register.php" class="text-decoration-none">Sign up</a>
                            </p>
                        </div>
                    </form>
                </div>
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
    
    // Auto-focus on username field
    document.getElementById('username').focus();
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
