<?php
require_once '../config/config.php';
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$success_message = '';
$error_message = '';
$valid_token = false;
$user_id = null;

// Check token
$token = $_GET['token'] ?? '';

if (!empty($token)) {
    // Check if token is valid and not expired
    $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $reset_data = $result->fetch_assoc();
    
    if ($reset_data) {
        $valid_token = true;
        $user_id = $reset_data['user_id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = 'Invalid CSRF token. Please try again.';
    } else {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (strlen($new_password) < 8) {
            $error_message = 'Password must be at least 8 characters long.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $conn->begin_transaction();
            
            try {
                // Update user password
                $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                $stmt->execute();
                
                // Delete used token
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                
                $conn->commit();
                
                $success_message = 'Password has been reset successfully! You can now login with your new password.';
                $valid_token = false; // Hide form after successful reset
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = 'Error updating password. Please try again.';
            }
        }
    }
}

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
    <title>Reset Password - CMS Admin</title>
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .reset-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        .reset-header {
            margin-bottom: 30px;
        }
        
        .reset-header i {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .reset-header i.fa-shield-alt {
            color: #28a745;
        }
        
        .reset-header i.fa-exclamation-triangle {
            color: #dc3545;
        }
        
        .reset-header h1 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 24px;
        }
        
        .reset-header p {
            color: #666;
            margin: 0;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group small {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
        
        .password-strength {
            margin-top: 5px;
            height: 4px;
            background: #e1e5e9;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
            border-radius: 2px;
        }
        
        .password-strength.weak .password-strength-bar {
            width: 25%;
            background-color: #dc3545;
        }
        
        .password-strength.fair .password-strength-bar {
            width: 50%;
            background-color: #ffc107;
        }
        
        .password-strength.good .password-strength-bar {
            width: 75%;
            background-color: #17a2b8;
        }
        
        .password-strength.strong .password-strength-bar {
            width: 100%;
            background-color: #28a745;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: left;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .back-link {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .reset-container {
                margin: 20px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <?php if (!$valid_token && empty($success_message)): ?>
            <div class="reset-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h1>Invalid Token</h1>
                <p>The password reset link is invalid or has expired.</p>
            </div>
            
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> This password reset link is no longer valid. Please request a new password reset.
            </div>
            
            <div class="back-link">
                <a href="forgot_password.php">
                    <i class="fas fa-arrow-left"></i> Request New Reset
                </a>
            </div>
            
        <?php elseif ($success_message): ?>
            <div class="reset-header">
                <i class="fas fa-check-circle" style="color: #28a745;"></i>
                <h1>Password Reset Complete</h1>
                <p>Your password has been successfully updated.</p>
            </div>
            
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
            
            <div class="back-link">
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </a>
            </div>
            
        <?php else: ?>
            <div class="reset-header">
                <i class="fas fa-shield-alt"></i>
                <h1>Reset Password</h1>
                <p>Enter your new password below.</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="resetForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required 
                           placeholder="Enter new password" minlength="8">
                    <div class="password-strength" id="passwordStrength">
                        <div class="password-strength-bar"></div>
                    </div>
                    <small>Password must be at least 8 characters long</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Confirm new password">
                    <small id="passwordMatch"></small>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </form>

            <div class="back-link">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const newPasswordField = document.getElementById('new_password');
        const confirmPasswordField = document.getElementById('confirm_password');
        const passwordStrength = document.getElementById('passwordStrength');
        const passwordMatch = document.getElementById('passwordMatch');
        const submitBtn = document.getElementById('submitBtn');

        // Password strength checker
        newPasswordField.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            updatePasswordStrengthIndicator(strength);
            checkPasswordMatch();
        });

        // Password match checker
        confirmPasswordField.addEventListener('input', checkPasswordMatch);

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
            passwordStrength.className = 'password-strength';
            
            if (strength === 0) {
                passwordStrength.classList.add('weak');
            } else if (strength <= 2) {
                passwordStrength.classList.add('weak');
            } else if (strength === 3) {
                passwordStrength.classList.add('fair');
            } else if (strength === 4) {
                passwordStrength.classList.add('good');
            } else {
                passwordStrength.classList.add('strong');
            }
        }

        function checkPasswordMatch() {
            const newPassword = newPasswordField.value;
            const confirmPassword = confirmPasswordField.value;
            
            if (confirmPassword.length === 0) {
                passwordMatch.textContent = '';
                passwordMatch.style.color = '';
                submitBtn.disabled = false;
                return;
            }
            
            if (newPassword === confirmPassword) {
                passwordMatch.textContent = 'Passwords match';
                passwordMatch.style.color = '#28a745';
                submitBtn.disabled = false;
            } else {
                passwordMatch.textContent = 'Passwords do not match';
                passwordMatch.style.color = '#dc3545';
                submitBtn.disabled = true;
            }
        }

        // Form validation
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const newPassword = newPasswordField.value;
            const confirmPassword = confirmPasswordField.value;
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }
        });

        // Auto-focus new password field
        newPasswordField.focus();
    </script>
</body>
</html>