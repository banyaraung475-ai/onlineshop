<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth.php';

// Ensure the user is an admin
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = new Database();
$message = '';
$error = '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
        // Proceed with form handling
    }
}

// Handle form submissions for add, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_user') {
            $full_name = sanitizeInput(trim($_POST['full_name'] ?? ''));
            $username = sanitizeInput(trim($_POST['username'] ?? ''));
            $email = sanitizeInput(trim($_POST['email'] ?? ''));
            $password = trim($_POST['password'] ?? '');
            // Admin add flow here is customer-only
            $role = 'customer';

            if ($username === '' || $email === '' || $password === '') {
                throw new Exception("Username, email, and password are required.");
            }
            if (!validateEmail($email)) {
                throw new Exception("Please enter a valid email address.");
            }
            if (strlen($password) < 8) {
                throw new Exception("Password must be at least 8 characters long.");
            }
            $existingUser = $db->fetchOne(
                "SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1",
                [$username, $email]
            );
            if ($existingUser) {
                throw new Exception("Username or email already exists.");
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $db->insert('users', [
                'username' => $username,
                'email' => $email,
                'password_hash' => $password_hash,
                'full_name' => $full_name,
                'role' => $role,
                'is_active' => 1,
                'email_verified' => 0
            ]);

            $message = "User added successfully.";
        } 
        elseif ($action === 'update_user') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            $full_name = sanitizeInput(trim($_POST['full_name'] ?? ''));
            $email = sanitizeInput(trim($_POST['email'] ?? ''));
            $password = trim($_POST['password'] ?? '');
            $role = $_POST['role'] ?? 'customer';
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($user_id <= 0) {
                throw new Exception("Invalid user selected.");
            }
            if ($email === '' || !validateEmail($email)) {
                throw new Exception("Please enter a valid email address.");
            }
            if (!in_array($role, ['customer', 'admin'], true)) {
                throw new Exception("Invalid role selected.");
            }
            if ($password !== '' && strlen($password) < 8) {
                throw new Exception("Password must be at least 8 characters long.");
            }

            $existingEmail = $db->fetchOne(
                "SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1",
                [$email, $user_id]
            );
            if ($existingEmail) {
                throw new Exception("Email is already in use by another account.");
            }

            $updateData = [
                'full_name' => $full_name,
                'email' => $email,
                'role' => $role,
                'is_active' => $is_active
            ];

            if ($password !== '') {
                $updateData['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $db->update('users', $updateData, 'user_id = ?', [$user_id]);
            $message = "User updated successfully.";
        }
        elseif ($action === 'delete_user') {
            $user_id_to_delete = (int)$_POST['user_id'];
            // Prevent admin from deleting themselves
            if ($user_id_to_delete === $_SESSION['user_id']) {
                throw new Exception("You cannot delete your own account.");
            }
            $db->delete('users', 'user_id = ?', [$user_id_to_delete]);
            $message = "User deleted successfully.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch all users to display
$users = $db->fetchAll("SELECT user_id, username, email, full_name, role, created_at, is_active FROM users ORDER BY created_at DESC");

// Fetch stats
$total_users = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
$admin_users = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")['count'];
$active_today = 0; // This would require tracking last login time, placeholder for now

$page_title = "Manage Users";
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
            <a href="admin_users.php" class="admin-nav-link active">
                <i class="bi bi-people me-2"></i> Users
            </a>
            <a href="admin_categories.php" class="admin-nav-link">
                <i class="bi bi-tags me-2"></i> Categories
            </a>
            <a href="admin_reviews.php" class="admin-nav-link">
                <i class="bi bi-chat-left-text me-2"></i> Reviews
            </a>
        </nav>
    </div>
</div>

<div class="admin-main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Manage Users</h1>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus me-2"></i>Add User
            </button>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- User Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number"><?php echo $admin_users; ?></div>
                <div class="stat-label">Admin Users</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number"><?php echo $active_today; ?></div>
                <div class="stat-label">Active Today</div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Users</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No users found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></div>
                                        <small class="text-muted">ID: #<?php echo $user['user_id']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'info'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary edit-user-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editUserModal"
                                            data-user-id="<?php echo $user['user_id']; ?>"
                                            data-full-name="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-role="<?php echo $user['role']; ?>"
                                            data-is-active="<?php echo $user['is_active']; ?>">
                                        Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_user">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="Enter full name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                    <input type="hidden" name="role" value="customer">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="user_id" id="edit-user-id">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="edit-full-name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" id="edit-email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" id="edit-role" class="form-select" required>
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit-is-active">
                        <label class="form-check-label" for="edit-is-active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit User Modal
    const editUserModal = document.getElementById('editUserModal');
    if (editUserModal) {
        editUserModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            // Extract info from data-* attributes
            const userId = button.getAttribute('data-user-id');
            const fullName = button.getAttribute('data-full-name') || '';
            const email = button.getAttribute('data-email') || '';
            const role = button.getAttribute('data-role') || 'customer';
            const isActive = button.getAttribute('data-is-active');

            // Update the modal's content.
            document.getElementById('edit-user-id').value = userId;
            document.getElementById('edit-full-name').value = fullName;
            document.getElementById('edit-email').value = email;
            document.getElementById('edit-role').value = role;
            document.getElementById('edit-is-active').checked = (isActive == 1);
        });
    }

    // View User Modal
    const viewUserModal = document.getElementById('viewUserModal');
    if (viewUserModal) {
        viewUserModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const modal = this;

            const userId = button.getAttribute('data-user-id');
            const fullName = button.getAttribute('data-full-name') || 'N/A';
            const username = button.getAttribute('data-username');
            const email = button.getAttribute('data-email');
            const role = button.getAttribute('data-role');
            const isActive = button.getAttribute('data-is-active');
            const createdAt = button.getAttribute('data-created-at');

            // Update modal content
            modal.querySelector('#view-full-name').textContent = fullName;
            modal.querySelector('#view-username').textContent = username;
            modal.querySelector('#view-email').textContent = email;
            modal.querySelector('#view-role').textContent = role.charAt(0).toUpperCase() + role.slice(1);
            modal.querySelector('#view-role').className = `badge bg-${role === 'admin' ? 'primary' : 'info'}`;
            modal.querySelector('#view-status').textContent = isActive == 1 ? 'Active' : 'Inactive';
            modal.querySelector('#view-status').className = `badge bg-${isActive == 1 ? 'success' : 'secondary'}`;
            modal.querySelector('#view-created-at').textContent = new Date(createdAt).toLocaleDateString();

            // Update avatar
            const avatar = modal.querySelector('#view-avatar');
            avatar.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(fullName)}&background=random&size=100`;

            // Set data for edit button
            const editBtn = modal.querySelector('.edit-from-view');
            editBtn.setAttribute('data-user-id', userId);
            editBtn.setAttribute('data-full-name', fullName);
            editBtn.setAttribute('data-email', email);
            editBtn.setAttribute('data-role', role);
            editBtn.setAttribute('data-is-active', isActive);
        });
    }

    // AJAX for add form
    const addForm = document.querySelector('#addUserModal form');
    addForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'add_user');
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes('User added successfully')) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
                modal.hide();
                // Reset form
                this.reset();
                // Refresh page to show new user
                location.reload();
            } else {
                alert('Error adding user. Please check the data and try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding user. Please try again.');
        });
    });

    // AJAX for edit form
    const editForm = document.querySelector('#editUserModal form');
    editForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'update_user');
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            // Check if update was successful (simple check)
            if (data.includes('User updated successfully')) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
                modal.hide();
                
                // Refresh the page to show updated data
                location.reload();
            } else {
                alert('Error updating user. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating user. Please try again.');
        });
    });

    // AJAX for delete
    document.addEventListener('submit', function(e) {
        if (e.target.matches('form[action="delete_user"]')) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this user?')) {
                return;
            }
            
            const formData = new FormData(e.target);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes('User deleted successfully')) {
                    // Remove the row
                    e.target.closest('tr').remove();
                    // Update stats if needed
                    location.reload(); // Simple way to update stats
                } else {
                    alert('Error deleting user. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting user. Please try again.');
            });
        }
    });

    // Live search functionality
    const searchInput = document.getElementById('userSearch');
    const table = document.getElementById('usersTable');
    if (searchInput && table && table.getElementsByTagName('tbody')[0]) {
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        searchInput.addEventListener('keyup', function() {
            const filter = searchInput.value.toLowerCase();
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let match = false;
                for (let j = 0; j < cells.length - 1; j++) { // Exclude actions column
                    if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                        match = true;
                        break;
                    }
                }
                rows[i].style.display = match ? '' : 'none';
            }
        });
    }

    // Auto-refresh stats every 5 minutes
    setInterval(function() {
        // In a real implementation, you could fetch updated stats via AJAX
        // For now, just log to console
        console.log('Refreshing stats...');
    }, 300000); // 5 minutes
});
</script>

<?php include_once __DIR__ . '/includes/admin_footer.php'; ?>
