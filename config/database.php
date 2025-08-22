<?php
/**
 * Secure Database Configuration and Connection Class
 * Implements comprehensive security measures and validation
 */

class DatabaseConfig {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $connection;
    private $options;
    
    public function __construct() {
        // Load configuration from environment or config file
        $this->loadConfig();
        
        // Set PDO options for security and performance
        $this->options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
    }
    
    private function loadConfig() {
        // Load from config file or environment variables
        if (file_exists(__DIR__ . '/database_credentials.php')) {
            $config = require __DIR__ . '/database_credentials.php';
            $this->host = $config['host'];
            $this->dbname = $config['dbname'];
            $this->username = $config['username'];
            $this->password = $config['password'];
        } else {
            // Fallback to environment variables
            $this->host = $_ENV['DB_HOST'] ?? 'localhost';
            $this->dbname = $_ENV['DB_NAME'] ?? 'cms_database';
            $this->username = $_ENV['DB_USER'] ?? '';
            $this->password = $_ENV['DB_PASS'] ?? '';
        }
    }
    
    public function connect() {
        try {
            if ($this->connection === null) {
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
                $this->connection = new PDO($dsn, $this->username, $this->password, $this->options);
            }
            return $this->connection;
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    public function disconnect() {
        $this->connection = null;
    }
}

/**
 * Input Validation Class with comprehensive security measures
 */
class InputValidator {
    
    /**
     * Validate and sanitize text input with length limits
     */
    public static function validateText($input, $maxLength = 50, $required = true) {
        // Required field validation
        if ($required && (empty($input) || trim($input) === '')) {
            throw new InvalidArgumentException("This field is required");
        }
        
        // Length validation
        if (strlen($input) > $maxLength) {
            throw new InvalidArgumentException("Text exceeds maximum length of {$maxLength} characters");
        }
        
        // XSS prevention
        $sanitized = htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        
        return $sanitized;
    }
    
    /**
     * Validate email with comprehensive checks
     */
    public static function validateEmail($email, $required = true) {
        if ($required && empty($email)) {
            throw new InvalidArgumentException("Email is required");
        }
        
        if (!empty($email)) {
            // Length validation
            if (strlen($email) > 100) {
                throw new InvalidArgumentException("Email exceeds maximum length of 100 characters");
            }
            
            // Format validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Invalid email format");
            }
            
            // Additional security check
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        }
        
        return $email;
    }
    
    /**
     * Validate integer input
     */
    public static function validateInteger($input, $min = null, $max = null, $required = true) {
        if ($required && ($input === '' || $input === null)) {
            throw new InvalidArgumentException("Integer value is required");
        }
        
        if (!is_numeric($input) || (int)$input != $input) {
            throw new InvalidArgumentException("Invalid integer value");
        }
        
        $value = (int)$input;
        
        if ($min !== null && $value < $min) {
            throw new InvalidArgumentException("Value must be at least {$min}");
        }
        
        if ($max !== null && $value > $max) {
            throw new InvalidArgumentException("Value must not exceed {$max}");
        }
        
        return $value;
    }
    
    /**
     * Validate slug (URL-safe string)
     */
    public static function validateSlug($input, $required = true) {
        if ($required && empty($input)) {
            throw new InvalidArgumentException("Slug is required");
        }
        
        // Length validation
        if (strlen($input) > 255) {
            throw new InvalidArgumentException("Slug exceeds maximum length of 255 characters");
        }
        
        // Slug format validation
        if (!preg_match('/^[a-z0-9-]+$/', $input)) {
            throw new InvalidArgumentException("Slug can only contain lowercase letters, numbers, and hyphens");
        }
        
        return $input;
    }
    
    /**
     * Generate secure slug from title
     */
    public static function generateSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $allowedTypes = ['image/jpeg'], $maxSize = 307200) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException("File upload failed");
        }
        
        // Size validation
        if ($file['size'] > $maxSize) {
            $maxSizeKB = $maxSize / 1024;
            throw new InvalidArgumentException("File size exceeds maximum of {$maxSizeKB}KB");
        }
        
        // MIME type validation
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new InvalidArgumentException("Invalid file type. Only JPG images are allowed");
        }
        
        // Additional security: check if file is actually an image
        if (strpos($mimeType, 'image/') === 0) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                throw new InvalidArgumentException("File is not a valid image");
            }
        }
        
        return true;
    }
    
    /**
     * Validate HTML content for rich text modules
     */
    public static function validateHtmlContent($content) {
        // Allow basic HTML tags but strip potentially dangerous ones
        $allowedTags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img>';
        
        $sanitized = strip_tags($content, $allowedTags);
        
        // Remove potentially dangerous attributes
        $sanitized = preg_replace('/(<[^>]+)\s+on\w+\s*=\s*["\'][^"\']*["\']([^>]*>)/i', '$1$2', $sanitized);
        $sanitized = preg_replace('/(<[^>]+)\s+javascript:[^>]*>/i', '$1>', $sanitized);
        
        return $sanitized;
    }
}

/**
 * CSRF Protection Class
 */
class CSRFProtection {
    
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    public static function validateToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if token exists and is not expired (30 minutes)
        if (!isset($_SESSION['csrf_token']) || 
            !isset($_SESSION['csrf_token_time']) ||
            time() - $_SESSION['csrf_token_time'] > 1800) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Secure Session Management
 */
class SecureSession {
    
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.use_strict_mode', 1);
            
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['last_regeneration'])) {
                self::regenerateId();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) {
                self::regenerateId();
            }
        }
    }
    
    public static function regenerateId() {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    public static function destroy() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }
    
    public static function isLoggedIn() {
        self::start();
        return isset($_SESSION['user_id']) && isset($_SESSION['user_logged_in']);
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
}

/**
 * Error Handling and Logging
 */
class ErrorHandler {
    
    public static function logError($message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? json_encode($context) : '';
        $logMessage = "[{$timestamp}] {$message} {$contextString}" . PHP_EOL;
        
        error_log($logMessage, 3, __DIR__ . '/../logs/cms_errors.log');
    }
    
    public static function handleException($exception) {
        self::logError("Exception: " . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Don't show detailed errors to users in production
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "Error: " . $exception->getMessage();
        } else {
            echo "An error occurred. Please try again later.";
        }
    }
}

// Set global exception handler
set_exception_handler(['ErrorHandler', 'handleException']);

// Database connection singleton
class DB {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            $config = new DatabaseConfig();
            self::$instance = $config->connect();
        }
        return self::$instance;
    }
}
?>