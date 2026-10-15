<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'includes/auth.php';
require_once 'classes/User.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = basename($_SERVER['PHP_SELF']);
    header("Location: login.php");
    exit();
}

$user = new User($db);
$userDetails = $user->getUserById($_SESSION['user_id']);

if (!$userDetails) {
    // Session user not found in DB; force re-login to avoid null dereferences
    session_destroy();
    header("Location: login.php");
    exit();
}

// Initialize messages
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_profile'])) {
            // Sanitize inputs
            $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }
            
            if ($user->updateUser($_SESSION['user_id'], $full_name, $email, $address, $phone)) {
                $success = "Profile updated successfully!";
                $userDetails = $user->getUserById($_SESSION['user_id']); // Refresh data
            } else {
                throw new Exception("Failed to update profile");
            }
            
        } elseif (isset($_POST['change_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validate password
            if (strlen($new_password) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match");
            }
            if ($current_password === $new_password) {
                throw new Exception("Current password cannot be the same as new password. Try again.");
            }
            // Verify current password using Database wrapper (PDO)
            $user_data = $db->fetchOne(
                "SELECT password_hash FROM users WHERE user_id = ?",
                [$_SESSION['user_id']]
            );

            if (!$user_data || !password_verify($current_password, $user_data['password_hash'])) {
                throw new Exception("Current password is incorrect");
            }
            
            if ($user->changePassword($_SESSION['user_id'], $new_password)) {
                $success = "Password changed successfully!";
            } else {
                throw new Exception("Failed to change password");
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = "My Account";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="position-relative mb-3">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($userDetails['full_name'] ?? $userDetails['username']); ?>&background=random&size=150" 
                             class="rounded-circle border" 
                             width="150" 
                             height="150"
                             alt="Profile">
                        <div class="position-absolute bottom-0 end-0 bg-primary rounded-circle p-2 border border-3 border-white">
                            <i class="bi bi-pencil-fill text-white"></i>
                        </div>
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($userDetails['full_name'] ?? $userDetails['username'] ?? ''); ?></h5>
                    <small class="text-muted">@<?php echo htmlspecialchars($userDetails['username'] ?? ''); ?></small>
                    
                    <div class="list-group list-group-flush mt-4">
                        <a href="my-account.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-person-fill me-2"></i> My Profile
                        </a>
                        <a href="my-orders.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-bag-check me-2"></i> My Orders
                        </a>
                       
                        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h4 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>My Account</h4>
                </div>
                
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <ul class="nav nav-tabs mb-4" id="accountTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button">
                                <i class="bi bi-person me-1"></i> Profile
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button">
                                <i class="bi bi-shield-lock me-1"></i> Password
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="accountTabsContent">
                        <!-- Profile Tab -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <form method="POST" id="profileForm">
                                <input type="hidden" name="update_profile">
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" 
                                               value="<?php echo htmlspecialchars($userDetails['username'] ?? ''); ?>" disabled>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($userDetails['email'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($userDetails['full_name'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"><?php 
                                            echo htmlspecialchars($userDetails['address'] ?? ''); 
                                        ?></textarea>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($userDetails['phone'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-12 mt-4">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="bi bi-save me-2"></i> Save Changes
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Password Tab -->
                        <div class="tab-pane fade" id="password" role="tabpanel">
                            <form method="POST" id="passwordForm">
                                <input type="hidden" name="change_password">
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <div class="form-text">Minimum 8 characters</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <div class="col-12 mt-4">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="bi bi-key me-2"></i> Change Password
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Password strength indicator
    $('#new_password').on('keyup', function() {
        const password = $(this).val();
        const strength = checkPasswordStrength(password);
        updateStrengthIndicator(strength);
    });
    
    function checkPasswordStrength(password) {
        // Implement your password strength logic here
        if (password.length === 0) return 0;
        if (password.length < 8) return 1;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password) && /[0-9]/.test(password)) return 3;
        return 2;
    }
    
    function updateStrengthIndicator(strength) {
        // Update UI based on password strength
        // Implement as needed
    }
    
    // Form validation
    $('#passwordForm').submit(function() {
        if ($('#new_password').val() !== $('#confirm_password').val()) {
            alert('Passwords do not match');
            return false;
        }
        return true;
    });
});
</script>

<?php include 'includes/footer.php'; ?>
