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


// Add this to your config/config.php file if not already present

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
define('SESSION_TIMEOUT', 900); // 15 minutes

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
        return isset($_SESSION['user_id']) && isset($_SESSION['username']);
    }
    
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getUsername() {
        return $_SESSION['username'] ?? null;
    }
    
    public static function login($userId, $username) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['last_activity'] = time();
        $_SESSION['last_regeneration'] = time();
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
 * Authentication Class
 */
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = DB::getInstance();
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
        SecureSession::login($user['id'], $user['username']);
        
        return true;
    }
    
    private function getUserByUsernameOrEmail($identifier) {
        $stmt = $this->db->prepare("
            SELECT id, username, email, password, status 
            FROM users 
            WHERE (username = ? OR email = ?) AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$identifier, $identifier]);
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
    }
    
    private function recordFailedAttempt($username) {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (username, ip_address, attempt_time) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$username, $_SERVER['REMOTE_ADDR'] ?? '']);
    }
    
    private function clearFailedAttempts($username) {
        $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE username = ?");
        $stmt->execute([$username]);
    }
    
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
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
    SecureSession::start();
    if (!SecureSession::isLoggedIn()) {
        redirect(ADMIN_URL . '/login.php', 'Please log in to access this page.', 'error');
    }
}

// Initialize secure session
SecureSession::start();
