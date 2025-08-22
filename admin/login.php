<?php
require_once '../config/config.php';

// Redirect if already logged in
if (SecureSession::isLoggedIn()) {
    redirect(ADMIN_URL . '/dashboard.php');
}

$auth = new Auth();
$error = '';
$showCaptcha = false;

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!CSRFProtection::validateToken($_POST['csrf_token'] ?? '')) {
            throw new Exception("Invalid request. Please try again.");
        }
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $captchaResponse = $_POST['g-recaptcha-response'] ?? '';
        
        $auth->login($username, $password, $captchaResponse);
        redirect(ADMIN_URL . '/dashboard.php', 'Login successful!', 'success');
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        // Check if we need to show CAPTCHA
        if (!empty($username)) {
            $showCaptcha = checkIfCaptchaRequired($username);
        }
    }
}

// Check if CAPTCHA is required for this username
function checkIfCaptchaRequired($username) {
    try {
        $db = DB::getInstance();
        $stmt = $db->prepare("
            SELECT COUNT(*) as attempts
            FROM login_attempts 
            WHERE username = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$username, LOCKOUT_DURATION]);
        $result = $stmt->fetch();
        return $result['attempts'] >= 2;
    } catch (Exception $e) {
        return false;
    }
}

// Get security settings for CAPTCHA
function getSecuritySettings() {
    try {
        $db = DB::getInstance();
        $stmt = $db->prepare("SELECT * FROM security_settings LIMIT 1");
        $stmt->execute();
        return $stmt->fetch() ?: [];
    } catch (Exception $e) {
        return [];
    }
}

$securitySettings = getSecuritySettings();
$csrfToken = CSRFProtection::generateToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - CMS</title>
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
        
        .login-container {
            width: 100%;
            max-width: 370px;
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
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 38, 155, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }
        
        .forgot-password {
            margin-top: 20px;
        }
        
        .forgot-password a {
            color: #1a269b;
            text-decoration: none;
            font-size: 14px;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .captcha-container {
            margin: 20px 0;
            display: flex;
            justify-content: center;
        }
        
        .footer-text {
            color: #666;
            font-size: 12px;
            margin-top: 20px;
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
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
        
        .loading .btn-login::after {
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
    
    <?php if ($showCaptcha && !empty($securitySettings['captcha_site_key'])): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
</head>
<body>
    <div class="login-container">
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
        
        <!-- Login Form -->
        <div class="form-container">
            <h1 class="form-title">Admin Login</h1>
            
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           value="<?= e($_POST['username'] ?? '') ?>"
                           required 
                           autocomplete="username"
                           maxlength="50">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           required 
                           autocomplete="current-password">
                </div>
                
                <?php if ($showCaptcha && !empty($securitySettings['captcha_site_key'])): ?>
                <div class="captcha-container">
                    <div class="g-recaptcha" data-sitekey="<?= e($securitySettings['captcha_site_key']) ?>"></div>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn-login">
                    Sign In
                </button>
                
                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot your password?</a>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="footer-text">
            By YD Multimedia <?= date('Y') ?>
        </div>
    </div>
    
    <script>
        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            this.classList.add('loading');
        });
        
        // Client-side validation
        document.getElementById('username').addEventListener('input', function() {
            if (this.value.length > 50) {
                this.setCustomValidity('Username must be 50 characters or less');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Remove error message on input
        document.querySelectorAll('.form-control').forEach(function(input) {
            input.addEventListener('input', function() {
                const errorMsg = document.querySelector('.error-message');
                if (errorMsg) {
                    errorMsg.style.opacity = '0.5';
                }
            });
        });
    </script>
</body>
</html>