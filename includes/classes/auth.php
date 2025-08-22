<?php
/**
 * Authentication and User Management Class
 * Handles login, logout, password management with security features
 */

class Auth {
    private $db;
    private $maxAttempts;
    private $lockoutDuration;
    
    public function __construct() {
        $this->db = DB::getInstance();
        $this->maxAttempts = MAX_LOGIN_ATTEMPTS;
        $this->lockoutDuration = LOCKOUT_DURATION;
    }
    
    /**
     * Authenticate user login with brute force protection
     */
    public function login($username, $password, $captchaResponse = null) {
        try {
            // Validate inputs
            $username = InputValidator::validateText($username, 50, true);
            
            if (empty($password)) {
                throw new InvalidArgumentException("Password is required");
            }
            
            // Check if account is locked
            if ($this->isAccountLocked($username)) {
                throw new Exception("Account is temporarily locked due to too many failed attempts");
            }
            
            // Verify CAPTCHA if required
            if ($this->requiresCaptcha($username) && !$this->verifyCaptcha($captchaResponse)) {
                throw new Exception("CAPTCHA verification failed");
            }
            
            // Get user from database
            $stmt = $this->db->prepare("
                SELECT id, username, email, password, full_name, status 
                FROM users 
                WHERE (username = ? OR email = ?) AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($password, $user['password'])) {
                $this->recordFailedAttempt($username);
                throw new Exception("Invalid username or password");
            }
            
            // Clear failed attempts on successful login
            $this->clearFailedAttempts($username);
            
            // Create secure session
            $this->createSession($user);
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            return true;
            
        } catch (Exception $e) {
            ErrorHandler::logError("Login attempt failed", [
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Create secure user session
     */
    private function createSession($user) {
        SecureSession::start();
        SecureSession::regenerateId();
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_logged_in'] = true;
        $_SESSION['login_time'] = time();
    }
    
    /**
     * Check if account is locked due to failed attempts
     */
    private function isAccountLocked($username) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt
            FROM login_attempts 
            WHERE username = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$username, $this->lockoutDuration]);
        $result = $stmt->fetch();
        
        return $result['attempts'] >= $this->maxAttempts;
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt($username) {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (username, ip_address, attempt_time) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$username, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    }
    
    /**
     * Clear failed attempts after successful login
     */
    private function clearFailedAttempts($username) {
        $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE username = ?");
        $stmt->execute([$username]);
    }
    
    /**
     * Check if CAPTCHA is required (after 2 failed attempts)
     */
    private function requiresCaptcha($username) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts
            FROM login_attempts 
            WHERE username = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$username, $this->lockoutDuration]);
        $result = $stmt->fetch();
        
        return $result['attempts'] >= 2;
    }
    
    /**
     * Verify Google reCAPTCHA
     */
    private function verifyCaptcha($captchaResponse) {
        if (empty($captchaResponse)) {
            return false;
        }
        
        // Get CAPTCHA settings from database
        $settings = $this->getSecuritySettings();
        if (empty($settings['captcha_secret_key'])) {
            return true; // Skip if not configured
        }
        
        $data = [
            'secret' => $settings['captcha_secret_key'],
            'response' => $captchaResponse,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($verify);
        curl_close($verify);
        
        $responseData = json_decode($response, true);
        return $responseData['success'] ?? false;
    }
    
    /**
     * Update user's last login timestamp
     */
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    /**
     * User logout
     */
    public function logout() {
        SecureSession::start();
        
        // Log logout activity
        if (isset($_SESSION['user_id'])) {
            ErrorHandler::logError("User logout", [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? 'unknown'
            ]);
        }
        
        SecureSession::destroy();
    }
    
    /**
     * Check if user is currently logged in
     */
    public function isLoggedIn() {
        return SecureSession::isLoggedIn();
    }
    
    /**
     * Get current user information
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        SecureSession::start();
        $userId = $_SESSION['user_id'];
        
        $stmt = $this->db->prepare("
            SELECT id, username, email, full_name, created_at, last_login
            FROM users 
            WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$userId]);
        
        return $stmt->fetch();
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $fullName, $email, $newPassword = null) {
        try {
            // Validate inputs
            $fullName = InputValidator::validateText($fullName, 100, true);
            $email = InputValidator::validateEmail($email, true);
            
            // Check email uniqueness
            $stmt = $this->db->prepare("
                SELECT id FROM users WHERE email = ? AND id != ?
            ");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                throw new Exception("Email address is already in use");
            }
            
            // Prepare update query
            $sql = "UPDATE users SET full_name = ?, email = ?, updated_at = NOW()";
            $params = [$fullName, $email];
            
            // Add password if provided
            if (!empty($newPassword)) {
                $this->validatePassword($newPassword);
                $sql .= ", password = ?";
                $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $userId;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            // Update session data
            SecureSession::start();
            $_SESSION['full_name'] = $fullName;
            
            return true;
            
        } catch (Exception $e) {
            ErrorHandler::logError("Profile update failed", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Validate password strength
     */
    private function validatePassword($password) {
        if (strlen($password) < 8) {
            throw new InvalidArgumentException("Password must be at least 8 characters long");
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            throw new InvalidArgumentException("Password must contain at least one uppercase letter, one lowercase letter, and one number");
        }
    }
    
    /**
     * Password reset functionality
     */
    public function requestPasswordReset($email) {
        try {
            $email = InputValidator::validateEmail($email, true);
            
            $stmt = $this->db->prepare("
                SELECT id, username, full_name FROM users 
                WHERE email = ? AND status = 'active'
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Don't reveal if email exists
                return true;
            }
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            
            // Store reset token
            $stmt = $this->db->prepare("
                INSERT INTO password_resets (user_id, token, expires_at) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token = ?, expires_at = ?
            ");
            $stmt->execute([$user['id'], $token, $expiry, $token, $expiry]);
            
            // Send reset email (implement email sending)
            $this->sendPasswordResetEmail($email, $user['full_name'], $token);
            
            return true;
            
        } catch (Exception $e) {
            ErrorHandler::logError("Password reset request failed", [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Reset password with token
     */
    public function resetPassword($token, $newPassword) {
        try {
            $this->validatePassword($newPassword);
            
            $stmt = $this->db->prepare("
                SELECT pr.user_id, u.username 
                FROM password_resets pr
                JOIN users u ON pr.user_id = u.id
                WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0
            ");
            $stmt->execute([$token]);
            $reset = $stmt->fetch();
            
            if (!$reset) {
                throw new Exception("Invalid or expired reset token");
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $reset['user_id']]);
            
            // Mark token as used
            $stmt = $this->db->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            return true;
            
        } catch (Exception $e) {
            ErrorHandler::logError("Password reset failed", [
                'token' => $token,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get security settings
     */
    private function getSecuritySettings() {
        $stmt = $this->db->prepare("SELECT * FROM security_settings LIMIT 1");
        $stmt->execute();
        return $stmt->fetch() ?: [];
    }
    
    /**
     * Send password reset email (placeholder - implement with your email system)
     */
    private function sendPasswordResetEmail($email, $name, $token) {
        $resetUrl = BASE_URL . "/admin/reset_password.php?token=" . $token;
        
        // This is a placeholder - implement with your email system
        // You can use PHPMailer or similar library
        
        $subject = "Password Reset Request";
        $message = "Hi {$name},\n\n";
        $message .= "You requested a password reset. Click the link below to reset your password:\n\n";
        $message .= $resetUrl . "\n\n";
        $message .= "This link will expire in 1 hour.\n\n";
        $message .= "If you didn't request this reset, please ignore this email.";
        
        // Log email sending attempt
        ErrorHandler::logError("Password reset email sent", [
            'email' => $email,
            'token' => $token
        ]);
    }
}
?>