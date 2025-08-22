<?php
/**
 * Password Reset Handler
 * Handles password reset via email token
 */

require_once '../config/config.php';

// Redirect if already logged in
if (SecureSession::isLoggedIn()) {
    redirect(ADMIN_URL . '/dashboard.php');
}

$token = $_GET['token'] ?? '';
$message = '';
$messageType = 'info';
$tokenValid = false;

// Validate token
if (!empty($token)) {
    try {
        $db = DB::getInstance();
        
        // Check if token is valid and not expired
        $stmt = $db->prepare("
            SELECT pr.*, u.username, u.email 
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0
        ");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if ($reset) {
            $tokenValid = true;
        } else {
            $message = 'This password reset link is invalid or has expired. Please request a new one.';
            $messageType = 'error';
        }
        
    } catch (Exception $e) {
        $message = 'An error occurred while validating the reset link. Please try again.';
        $messageType = 'error';
        ErrorHandler::logError("Password reset token validation failed: " . $e->getMessage());
    }
} else {
    $message = 'No reset token provided. Please check your email for the correct link.';
    $messageType = 'error';
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    try {
        // Validate CSRF token
        if (!CSRFProtection::validateToken($_POST['csrf_token'] ?? '')) {
            throw new Exception("Invalid request. Please try again.");
        }
        
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($newPassword) || empty($confirmPassword)) {
            throw new Exception("All fields are required");
        }
        
        if ($newPassword !== $confirmPassword) {
            throw new Exception("Passwords do not match");
        }
        
        if (strlen($newPassword) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $newPassword)) {
            throw new Exception("Password must contain at least one uppercase letter, one lowercase letter, and one number");
        }
        
        // Reset password using Auth class
        $auth = new Auth();
        $auth->resetPassword($token, $newPassword);
        
        redirect('login.php', 'Your password has been reset successfully. You can now login with your new password.', 'success');
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

$csrfToken = CSRFProtection::generateToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CMS Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #d5dce5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .reset-container {
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        .logo-container {
            background: white;
            border-radius: 25px;
            padding: 20px;
            margin-bottom: 20px;
            height: 170px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .logo-container img {
            max-width: 100%;
            max-height: 130px;
            object-fit: contain;
        }
        
        .logo-placeholder {
            color: #666;
            font-size: 24px;
            font-weight: bold;
        }
        
        .form-container {
            background: #fbfbfb;
            border-radius: 25px;
            padding: 40px 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            margin-bottom: 20px;
        }
        
        .form-title {
            color: #333;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 30px;
        }
        
        .form-subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #1a269b;
            box-shadow: 0 0 0 3px rgba(26, 38, 155, 0.1);
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a269b, #2d3db4);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 38, 155, 0.3);
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .message.success {
            background: #efe;
            color: #363;
            border-left-color: #4caf50;
        }
        
        .message.error {
            background: #fee;
            color: #c33;
            border-left-color: #f44336;
        }
        
        .message.info {
            background: #e3f2fd;
            color: #1976d2;
            border-left-color: #2196f3;
        }
        
        .back-to-login {
            margin-top: 20px;
        }
        
        .back-to-login a {
            color: #1a269b;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .back-to-login a:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: left;
        }
        
        .password-requirements h4 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
            color: #666;
        }
        
        .password-requirements li {
            margin-bottom: 5px;
        }
        
        .security-notice {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: left;
        }
        
        .security-notice h4 {
            margin-bottom: 10px;
            color: #856404;
        }
        
        .security-notice ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .security-notice li {
            margin-bottom: 5px;
        }
        
        .footer-text {
            color: #666;
            font-size: 12px;
            margin-top: 20px;
        }
        
        .token-info {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: left;
            border-left: 4px solid #4caf50;
        }
        
        .token-info h4 {
            margin-bottom: 10px;
            color: #2e7d32;
        }
        
        .error-container {
            text-align: center;
            padding: 40px 20px;
        }
        
        .error-icon {
            font-size: 4rem;
            color: #f44336;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .error-description {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn-link {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #1a269b, #2d3db4);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        
        .btn-link:hover {
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .reset-container {
                max-width: 300px;
            }
            
            .form-container {
                padding: 30px 20px;
            }
            
            .logo-container {
                height: 140px;
            }
        }
        
        /* Loading Animation */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .loading .btn-submit::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-left: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <!-- Logo Container -->
        <div class="logo-container">
            <?php
            // Try to get login logo from theme settings
            try {
                $db = DB::getInstance();
                $stmt = $db->prepare("SELECT login_logo FROM theme_settings LIMIT 1");
                $stmt->execute();
                $theme = $stmt->fetch();
                
                if ($theme && !empty($theme['login_logo']) && file_exists(UPLOADS_PATH . '/logos/' . $theme['login_logo'])) {
                    echo '<img src="' . UPLOADS_URL . '/logos/' . e($theme['login_logo']) . '" alt="Company Logo">';
                } else {
                    echo '<div class="logo-placeholder">CMS Admin</div>';
                }
            } catch (Exception $e) {
                echo '<div class="logo-placeholder">CMS Admin</div>';
            }
            ?>
        </div>
        
        <!-- Form Container -->
        <div class="form-container">
            <?php if ($tokenValid): ?>
                <h1 class="form-title">Reset Password</h1>
                <p class="form-subtitle">
                    Please enter your new password below.
                </p>
                
                <?php if (!empty($message)): ?>
                    <div class="message <?= e($messageType) ?>">
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>
                
                <div class="token-info">
                    <h4><i class="fas fa-shield-alt"></i> Secure Reset</h4>
                    <p>This link is valid for one use only and will expire in 1 hour from when it was sent.</p>
                </div>
                
                <div class="password-requirements">
                    <h4>Password Requirements:</h4>
                    <ul>
                        <li id="req-length">At least 8 characters long</li>
                        <li id="req-upper">At least one uppercase letter</li>
                        <li id="req-lower">At least one lowercase letter</li>
                        <li id="req-number">At least one number</li>
                    </ul>
                </div>
                
                <form method="POST" id="resetPasswordForm">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               class="form-control" 
                               required 
                               minlength="8"
                               autocomplete="new-password"
                               placeholder="Enter your new password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="form-control" 
                               required 
                               autocomplete="new-password"
                               placeholder="Confirm your new password">
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        Reset Password
                    </button>
                </form>
                
                <div class="security-notice">
                    <h4><i class="fas fa-info-circle"></i> Security Tips:</h4>
                    <ul>
                        <li>Choose a password you haven't used before</li>
                        <li>Don't share your password with anyone</li>
                        <li>Consider using a password manager</li>
                        <li>Log out of all devices after resetting</li>
                    </ul>
                </div>
                
            <?php else: ?>
                <!-- Invalid Token Display -->
                <div class="error-container">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    
                    <h2 class="error-title">Invalid Reset Link</h2>
                    
                    <div class="error-description">
                        <?php if (!empty($message)): ?>
                            <p><?= e($message) ?></p>
                        <?php else: ?>
                            <p>This password reset link is invalid, expired, or has already been used.</p>
                        <?php endif; ?>
                        
                        <p>Password reset links are valid for 1 hour and can only be used once for security reasons.</p>
                    </div>
                    
                    <a href="forgot_password.php" class="btn-link">
                        <i class="fas fa-redo"></i>
                        Request New Reset Link
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="back-to-login">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to Login
                </a>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer-text">
            By YD Multimedia <?= date('Y') ?>
        </div>
    </div>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <script>
        // Form submission with loading state
        document.getElementById('resetPasswordForm')?.addEventListener('submit', function() {
            this.classList.add('loading');
        });
        
        // Password confirmation validation
        const confirmPassword = document.getElementById('confirm_password');
        const newPassword = document.getElementById('new_password');
        
        if (confirmPassword && newPassword) {
            confirmPassword.addEventListener('input', function() {
                if (this.value !== newPassword.value) {
                    this.setCustomValidity('Passwords do not match');
                    this.style.borderColor = '#f44336';
                } else {
                    this.setCustomValidity('');
                    this.style.borderColor = '#4caf50';
                }
            });
            
            // Real-time password strength indication
            newPassword.addEventListener('input', function() {
                const password = this.value;
                
                // Check requirements
                const requirements = [
                    { id: 'req-length', check: password.length >= 8 },
                    { id: 'req-upper', check: /[A-Z]/.test(password) },
                    { id: 'req-lower', check: /[a-z]/.test(password) },
                    { id: 'req-number', check: /\d/.test(password) }
                ];
                
                let validCount = 0;
                requirements.forEach(req => {
                    const element = document.getElementById(req.id);
                    if (req.check) {
                        element.style.color = '#4caf50';
                        element.style.textDecoration = 'line-through';
                        element.innerHTML = '<i class="fas fa-check"></i> ' + element.textContent.replace('✓ ', '');
                        validCount++;
                    } else {
                        element.style.color = '#666';
                        element.style.textDecoration = 'none';
                        element.innerHTML = element.textContent.replace('<i class="fas fa-check"></i> ', '');
                    }
                });
                
                // Update password field border color
                if (validCount === requirements.length) {
                    this.style.borderColor = '#4caf50';
                } else if (password.length > 0) {
                    this.style.borderColor = '#ffc107';
                } else {
                    this.style.borderColor = '#ddd';
                }
                
                // Re-validate confirm password if it has a value
                if (confirmPassword.value) {
                    confirmPassword.dispatchEvent(new Event('input'));
                }
            });
        }
        
        // Remove message on input
        document.querySelectorAll('.form-control').forEach(function(input) {
            input.addEventListener('input', function() {
                const message = document.querySelector('.message');
                if (message && message.classList.contains('error')) {
                    message.style.opacity = '0.5';
                }
            });
        });
        
        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('.form-control');
            if (firstInput) {
                firstInput.focus();
            }
            
            // Add enhanced security notice
            const securityNotice = document.querySelector('.security-notice');
            if (securityNotice) {
                securityNotice.addEventListener('click', function() {
                    this.style.backgroundColor = '#f0f8e7';
                    setTimeout(() => {
                        this.style.backgroundColor = '#fff3cd';
                    }, 200);
                });
            }
        });
        
        // Token expiration countdown (if needed)
        function startExpirationCountdown() {
            // This could be enhanced to show a real countdown
            // For now, just show a warning after 45 minutes
            setTimeout(function() {
                const warning = document.createElement('div');
                warning.className = 'message error';
                warning.innerHTML = '<i class="fas fa-clock"></i> This reset link will expire soon. Please complete your password reset.';
                
                const form = document.querySelector('form');
                if (form) {
                    form.parentNode.insertBefore(warning, form);
                }
            }, 45 * 60 * 1000); // 45 minutes
        }
        
        // Start countdown if form is present
        if (document.getElementById('resetPasswordForm')) {
            startExpirationCountdown();
        }
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>