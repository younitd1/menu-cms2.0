<?php
/**
 * Main Configuration File
 * Sets up database connection, constants, and autoloads classes
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load database credentials
$dbConfig = require_once __DIR__ . '/database_credentials.php';

// Define constants
define('ROOT_PATH', dirname(__DIR__));
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('DEFAULT_ITEMS_PER_PAGE', 10);
define('UPLOAD_IMAGES_PATH', UPLOADS_PATH . '/images');
define('UPLOAD_THUMBNAILS_PATH', UPLOADS_PATH . '/thumbnails');

// Add missing image constants
define('CATEGORY_BACKGROUND_WIDTH', 1600);
define('PRODUCT_IMAGE_MAX_WIDTH', 800);
define('MAX_FILE_SIZE', 307200); // 300KB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg']);

// Define module positions if not defined
if (!defined('MODULE_POSITIONS')) {
    define('MODULE_POSITIONS', [
        'top-module-1' => 'Top Module 1',
        'top-module-2' => 'Top Module 2', 
        'middle-module-1' => 'Middle Module 1',
        'middle-module-2' => 'Middle Module 2',
        'bottom-module-1' => 'Bottom Module 1',
        'bottom-module-2' => 'Bottom Module 2'
    ]);
}

// URL constants (adjust these based on your setup)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Get the base path correctly
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($scriptPath, '/admin/') !== false) {
    // If we're in admin folder, go up one level
    $basePath = dirname(dirname($scriptPath));
} else {
    $basePath = dirname($scriptPath);
}

define('BASE_URL', $protocol . '://' . $host . $basePath);
define('ADMIN_URL', BASE_URL . '/admin');
define('UPLOADS_URL', BASE_URL . '/uploads');

// Security constants
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes in seconds
define('SESSION_TIMEOUT', 1440); // 24 minutes

/**
 * Database Connection Class
 */
class DB {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        global $dbConfig;
        
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $this->pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->pdo;
    }
}

/**
 * Secure Session Management
 */
class SecureSession {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            session_start();
        }
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            self::destroy();
            return false;
        }
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true &&
               isset($_SESSION['user_id']) && isset($_SESSION['username']);
    }
    
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getUsername() {
        return $_SESSION['username'] ?? null;
    }
    
    public static function login($userId, $username, $fullName = '') {
        session_regenerate_id(true);
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['full_name'] = $fullName;
        $_SESSION['last_activity'] = time();
        $_SESSION['last_regeneration'] = time();
    }
    
    public static function logout() {
        self::destroy();
        redirect(ADMIN_URL . '/login.php', 'You have been logged out successfully.', 'info');
    }
    
    public static function requireLogin() {
        self::start();
        if (!self::isLoggedIn()) {
            redirect(ADMIN_URL . '/login.php', 'Please log in to access this page.', 'error');
        }
    }
    
    public static function destroy() {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}

/**
 * CSRF Protection
 */
class CSRFProtection {
    public static function generateToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateToken($token) {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Input Validator Class
 */
class InputValidator {
    public static function validateText($text, $maxLength = null, $required = false) {
        $text = trim($text ?? '');
        
        if ($required && empty($text)) {
            throw new Exception("Required field cannot be empty");
        }
        
        if ($maxLength && strlen($text) > $maxLength) {
            throw new Exception("Text exceeds maximum length of {$maxLength} characters");
        }
        
        return $text;
    }
    
    public static function validateInteger($value, $min = null, $max = null, $required = false) {
        if ($value === '' || $value === null) {
            if ($required) {
                throw new Exception("Required field cannot be empty");
            }
            return null;
        }
        
        $value = intval($value);
        
        if ($min !== null && $value < $min) {
            throw new Exception("Value must be at least {$min}");
        }
        
        if ($max !== null && $value > $max) {
            throw new Exception("Value must be at most {$max}");
        }
        
        return $value;
    }
    
    public static function validateEmail($email, $required = false) {
        $email = trim($email ?? '');
        
        if (empty($email)) {
            if ($required) {
                throw new Exception("Email is required");
            }
            return '';
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        return $email;
    }
    
    public static function validateSlug($slug) {
        $slug = trim($slug ?? '');
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    public static function generateSlug($text) {
        return self::validateSlug($text);
    }
    
    public static function validateHtmlContent($html) {
        // Basic HTML sanitization - you might want to use a library like HTMLPurifier for production
        return trim($html ?? '');
    }
    
    public static function validateFileUpload($file, $allowedTypes = ['image/jpeg'], $maxSize = 307200) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed");
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception("File size exceeds maximum allowed size");
        }
        
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Invalid file type");
        }
        
        return true;
    }
}

/**
 * Authentication Class
 */
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = DB::getInstance();
    }
    
    public function getCurrentUser() {
        if (!SecureSession::isLoggedIn()) {
            return null;
        }
        
        $userId = SecureSession::getUserId();
        $stmt = $this->db->prepare("SELECT id, username, email, full_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function login($username, $password, $captchaResponse = '') {
        // Check if account is locked
        if ($this->isAccountLocked($username)) {
            throw new Exception("Account temporarily locked due to multiple failed login attempts. Please try again later.");
        }
        
        // Validate CAPTCHA if required
        if ($this->isCaptchaRequired($username) && !$this->validateCaptcha($captchaResponse)) {
            throw new Exception("CAPTCHA validation failed. Please try again.");
        }
        
        // Get user from database
        $user = $this->getUserByUsernameOrEmail($username);
        
        if (!$user || !password_verify($password, $user['password'])) {
            $this->recordFailedAttempt($username);
            throw new Exception("Invalid username/email or password.");
        }
        
        if ($user['status'] !== 'active') {
            throw new Exception("Account is inactive. Please contact administrator.");
        }
        
        // Successful login
        $this->clearFailedAttempts($username);
        $this->updateLastLogin($user['id']);
        SecureSession::login($user['id'], $user['username'], $user['full_name'] ?? '');
        
        return true;
    }
    
    public function logout() {
        SecureSession::logout();
    }
    
    public function updateProfile($userId, $fullName, $email, $newPassword = null) {
        if ($newPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$fullName, $email, $hashedPassword, $userId]);
        } else {
            $stmt = $this->db->prepare("UPDATE users SET full_name = ?, email = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$fullName, $email, $userId]);
        }
    }
    
    public function requestPasswordReset($email) {
        $user = $this->getUserByEmail($email);
        if (!$user) {
            // Don't reveal if email exists or not
            return true;
        }
        
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store reset token
        $stmt = $this->db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
        $stmt->execute([$user['id'], $token, $expires, $token, $expires]);
        
        // Send email (implement email sending logic here)
        // For now, just return true
        return true;
    }
    
    public function resetPassword($token, $newPassword) {
        $stmt = $this->db->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if (!$reset) {
            throw new Exception("Invalid or expired reset token");
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password and delete reset token
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $reset['user_id']]);
            
            $stmt = $this->db->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    private function getUserByUsernameOrEmail($identifier) {
        $stmt = $this->db->prepare("
            SELECT id, username, email, password, status, full_name
            FROM users 
            WHERE (username = ? OR email = ?) AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$identifier, $identifier]);
        return $stmt->fetch();
    }
    
    private function getUserByEmail($email) {
        $stmt = $this->db->prepare("SELECT id, email FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    private function isAccountLocked($username) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts
            FROM login_attempts 
            WHERE username = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$username, LOCKOUT_DURATION]);
        $result = $stmt->fetch();
        
        return $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
    }
    
    private function isCaptchaRequired($username) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts
            FROM login_attempts 
            WHERE username = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        
        return $result['attempts'] >= 2;
    }
    
    private function validateCaptcha($response) {
        if (empty($response)) {
            return false;
        }
        
        // Get CAPTCHA secret from security settings
        try {
            $stmt = $this->db->prepare("SELECT captcha_secret_key FROM security_settings LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch();
            
            if (!$settings || empty($settings['captcha_secret_key'])) {
                return true; // Skip validation if not configured
            }
            
            $data = [
                'secret' => $settings['captcha_secret_key'],
                'response' => $response,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ];
            
            $verify = curl_init();
            curl_setopt($verify, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
            curl_setopt($verify, CURLOPT_POST, true);
            curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($verify, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($verify);
            curl_close($verify);
            
            if ($response === false) {
                return false;
            }
            
            $result = json_decode($response, true);
            return isset($result['success']) && $result['success'] === true;
        } catch (Exception $e) {
            return true; // Skip validation if error
        }
    }
    
    private function recordFailedAttempt($username) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO login_attempts (username, ip_address, attempt_time) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$username, $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (Exception $e) {
            // Fail silently
        }
    }
    
    private function clearFailedAttempts($username) {
        try {
            $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE username = ?");
            $stmt->execute([$username]);
        } catch (Exception $e) {
            // Fail silently
        }
    }
    
    private function updateLastLogin($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            // Fail silently
        }
    }
}

/**
 * Error Handler Class
 */
class ErrorHandler {
    public static function logError($message) {
        error_log($message);
    }
}

/**
 * Image Upload and Processing Functions
 */
function uploadImage($file, $uploadPath, $maxWidth = 800) {
    InputValidator::validateFileUpload($file, ALLOWED_IMAGE_TYPES, MAX_FILE_SIZE);
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = $uploadPath . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception("Failed to upload file");
    }
    
    // Resize image if needed
    resizeImage($filepath, $maxWidth);
    
    return $filename;
}

function resizeImage($filepath, $maxWidth) {
    $imageInfo = getimagesize($filepath);
    if (!$imageInfo) {
        return false;
    }
    
    list($width, $height, $type) = $imageInfo;
    
    if ($width <= $maxWidth) {
        return true; // No resize needed
    }
    
    $ratio = $maxWidth / $width;
    $newWidth = $maxWidth;
    $newHeight = intval($height * $ratio);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filepath);
            break;
        default:
            return false;
    }
    
    $destination = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($destination, $filepath, 90);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($destination);
    
    return true;
}

function generateThumbnail($imagePath, $thumbnailPath) {
    if (!is_dir($thumbnailPath)) {
        mkdir($thumbnailPath, 0755, true);
    }
    
    $filename = basename($imagePath);
    $thumbnailFile = $thumbnailPath . '/' . pathinfo($filename, PATHINFO_FILENAME) . '_thumb.' . pathinfo($filename, PATHINFO_EXTENSION);
    
    $imageInfo = getimagesize($imagePath);
    if (!$imageInfo) {
        return false;
    }
    
    list($width, $height, $type) = $imageInfo;
    
    $thumbWidth = 150;
    $thumbHeight = 150;
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($imagePath);
            break;
        default:
            return false;
    }
    
    $destination = imagecreatetruecolor($thumbWidth, $thumbHeight);
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($destination, $thumbnailFile, 80);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($destination);
    
    return true;
}

/**
 * Cache Functions
 */
function clearCache($pattern = '*') {
    // Implement cache clearing logic if you're using caching
    return true;
}

/**
 * Utility Functions
 */

// Escape HTML output
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Redirect function
function redirect($url, $message = '', $type = 'info') {
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

// Get flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// Require authentication for admin pages
function requireAuth() {
    SecureSession::requireLogin();
}

// Initialize secure session
SecureSession::start();
