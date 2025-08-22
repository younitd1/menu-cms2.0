<?php
/**
 * Forgot Password Page
 * Handles password reset requests
 */

require_once '../config/config.php';

// Redirect if already logged in
if (SecureSession::isLoggedIn()) {
    redirect(ADMIN_URL . '/dashboard.php');
}

$auth = new Auth();
$message = '';
$messageType = 'info';
$step = $_GET['step'] ?? 'request';
$token = $_GET['token'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!CSRFProtection::validateToken($_POST['csrf_token'] ?? '')) {
            throw new Exception("Invalid request. Please try again.");
        }
        
        if ($step === 'request') {
            // Handle password reset request
            $email = $_POST['email'] ?? '';
            $auth->requestPasswordReset($email);
            
            $message = 'If an account exists with that email address, you will receive password reset instructions.';
            $messageType = 'success';
            
        } elseif ($step === 'reset') {
            // Handle password reset
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception("Passwords do not match");
            }
            
            $auth->resetPassword($token, $newPassword);
            
            $message = 'Your password has been reset successfully. You can now login with your new password.';
            $messageType = 'success';
            $step = 'complete';
        }
        
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
    <title>Forgot Password - CMS Admin</title>
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
        
        .forgot-password-container {
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
        
        .footer-text {
            color: #666;
            font-size: 12px;
            margin-top: 20px;
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .forgot-password-container {
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
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ddd;
            margin: 0 5px;
            transition: background 0.3s ease;
        }
        
        .step.active {
            background: #1a269b;
        }
        
        .step.completed {
            background: #4caf50;
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
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
            <?php if ($step === 'request'): ?>
                <h1 class="form-title">Forgot Password</h1>
                <p class="form-subtitle">
                    Enter your email address and we'll send you a link to reset your password.
                </p>
                
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step active"></div>
                    <div class="step"></div>
                    <div class="step"></div>
                </div>
                
                <?php if (!empty($message)): ?>
                    <div class="message <?= e($messageType) ?>">
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="forgotPasswordForm">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               value="<?= e($_POST['email'] ?? '') ?>"
                               required 
                               autocomplete="email"
                               maxlength="100"
                               placeholder="Enter your email address">
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        Send Reset Link
                    </button>
                </form>
                
            <?php elseif ($step === 'reset'): ?>
                <h1 class="form-title">Reset Password</h1>
                <p class="form-subtitle">
                    Enter your new password below.
                </p>
                
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step completed"></div>
                    <div class="step active"></div>
                    <div class="step"></div>
                </div>
                
                <?php if (!empty($message)): ?>
                    <div class="message <?= e($messageType) ?>">
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>
                
                <div class="password-requirements">
                    <h4>Password Requirements:</h4>
                    <ul>
                        <li>At least 8 characters long</li>
                        <li>At least one uppercase letter</li>
                        <li>At least one lowercase letter</li>
                        <li>At least one number</li>
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
                               placeholder="Enter new password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="form-control" 
                               required 
                               autocomplete="new-password"
                               placeholder="Confirm new password">
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        Reset Password
                    </button>
                </form>
                
            <?php elseif ($step === 'complete'): ?>
                <h1 class="form-title">Password Reset Complete</h1>
                
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step completed"></div>
                    <div class="step completed"></div>
                    <div class="step completed"></div>
                </div>
                
                <?php if (!empty($message)): ?>
                    <div class="message <?= e($messageType) ?>">
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin: 30px 0;">
                    <i class="fas fa-check-circle" style="font-size: 4rem; color: #4caf50; margin-bottom: 20px;"></i>
                    <p style="font-size: 18px; color: #333; margin-bottom: 30px;">
                        Your password has been successfully reset!
                    </p>
                    <a href="login.php" class="btn-submit" style="display: inline-block; text-decoration: none; width: auto; padding: 12px 30px;">
                        Continue to Login
                    </a>
                </div>
                
            <?php endif; ?>
            
            <?php if ($step !== 'complete'): ?>
                <div class="back-to-login">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i>
                        Back to Login
                    </a>
                </div>
            <?php endif; ?>
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
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                this.classList.add('loading');
            });
        });
        
        // Password confirmation validation
        const confirmPassword = document.getElementById('confirm_password');
        const newPassword = document.getElementById('new_password');
        
        if (confirmPassword && newPassword) {
            confirmPassword.addEventListener('input', function() {
                if (this.value !== newPassword.value) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Real-time password strength indication
            newPassword.addEventListener('input', function() {
                const password = this.value;
                const requirements = document.querySelectorAll('.password-requirements li');
                
                // Check requirements
                const checks = [
                    password.length >= 8,
                    /[A-Z]/.test(password),
                    /[a-z]/.test(password),
                    /\d/.test(password)
                ];
                
                requirements.forEach((req, index) => {
                    if (checks[index]) {
                        req.style.color = '#4caf50';
                        req.style.textDecoration = 'line-through';
                    } else {
                        req.style.color = '#666';
                        req.style.textDecoration = 'none';
                    }
                });
            });
        }
        
        // Email validation
        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.addEventListener('input', function() {
                if (this.value.length > 100) {
                    this.setCustomValidity('Email must be 100 characters or less');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
        
        // Remove message on input
        document.querySelectorAll('.form-control').forEach(function(input) {
            input.addEventListener('input', function() {
                const message = document.querySelector('.message');
                if (message) {
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
        });
    </script>
</body>
</html>