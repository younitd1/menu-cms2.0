<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = 'Invalid CSRF token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_profile':
                $username = $database->sanitizeInput($_POST['username']);
                $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
                $full_name = $database->sanitizeInput($_POST['full_name']);
                
                if (!$email) {
                    $error_message = 'Please enter a valid email address.';
                } elseif (empty($username) || strlen($username) < 3) {
                    $error_message = 'Username must be at least 3 characters long.';
                } else {
                    // Check if username/email already exists (excluding current user)
                    $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                    $stmt->bind_param("ssi", $username, $email, $_SESSION['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $error_message = 'Username or email already exists.';
                    } else {
                        // Update profile
                        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->bind_param("sssi", $username, $email, $full_name, $_SESSION['user_id']);
                        
                        if ($stmt->execute()) {
                            $_SESSION['username'] = $username;
                            $success_message = 'Profile updated successfully!';
                        } else {
                            $error_message = 'Error updating profile. Please try again.';
                        }
                    }
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if (strlen($new_password) < 8) {
                    $error_message = 'New password must be at least 8 characters long.';
                } elseif ($new_password !== $confirm_password) {
                    $error_message = 'New passwords do not match.';
                } else {
                    // Verify current password
                    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    
                    if (!password_verify($current_password, $user['password'])) {
                        $error_message = 'Current password is incorrect.';
                    } else {
                        // Update password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                        
                        if ($stmt->execute()) {
                            $success_message = 'Password changed successfully!';
                        } else {
                            $error_message = 'Error changing password. Please try again.';
                        }
                    }
                }
                break;
        }
    }
}

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Management - CMS Admin</title>
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content-area">
                <div class="page-header">
                    <h1><i class="fas fa-user-circle"></i> Profile Management</h1>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="profile-container">
                    <!-- Profile Information -->
                    <div class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-user"></i> Profile Information</h2>
                        </div>
                        
                        <form method="POST" class="profile-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    <small>Username must be at least 3 characters long</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="full_name">Full Name</label>
                                    <input type="text" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-lock"></i> Change Password</h2>
                        </div>
                        
                        <form method="POST" class="profile-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" required>
                                    <small>Password must be at least 8 characters long</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Account Information -->
                    <div class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-info-circle"></i> Account Information</h2>
                        </div>
                        
                        <div class="account-info">
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>User ID</label>
                                    <span><?php echo htmlspecialchars($user['id']); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <label>Account Created</label>
                                    <span><?php echo date('F j, Y \a\t g:i A', strtotime($user['created_at'])); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <label>Last Updated</label>
                                    <span><?php echo $user['updated_at'] ? date('F j, Y \a\t g:i A', strtotime($user['updated_at'])) : 'Never'; ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <label>Last Login</label>
                                    <span><?php echo $user['last_login'] ? date('F j, Y \a\t g:i A', strtotime($user['last_login'])) : 'Never'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-shield-alt"></i> Security Settings</h2>
                        </div>
                        
                        <div class="security-info">
                            <div class="security-item">
                                <div class="security-icon">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                                <div class="security-content">
                                    <h4>Password Protection</h4>
                                    <p>Your account is protected with a secure password</p>
                                </div>
                            </div>
                            
                            <div class="security-item">
                                <div class="security-icon">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                                <div class="security-content">
                                    <h4>Session Security</h4>
                                    <p>Active session with automatic timeout protection</p>
                                </div>
                            </div>
                            
                            <div class="security-item">
                                <div class="security-icon">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                                <div class="security-content">
                                    <h4>CSRF Protection</h4>
                                    <p>All forms are protected against cross-site request forgery</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            updatePasswordStrengthIndicator(strength);
        });

        function calculatePasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            return strength;
        }

        function updatePasswordStrengthIndicator(strength) {
            // Add visual password strength indicator if needed
            console.log('Password strength:', strength);
        }

        // Confirm password match
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>