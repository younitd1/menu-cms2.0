<?php
/**
 * Main Configuration File
 * Contains all system-wide configurations and constants
 */

// Error reporting (set to false in production)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// System paths
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('LOGS_PATH', ROOT_PATH . '/logs');

// URL configuration
define('BASE_URL', 'http://localhost/cms'); // Change this to your domain
define('ADMIN_URL', BASE_URL . '/admin');
define('UPLOADS_URL', BASE_URL . '/uploads');

// File upload settings
define('MAX_FILE_SIZE', 307200); // 300KB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg']);
define('UPLOAD_IMAGES_PATH', UPLOADS_PATH . '/images');
define('UPLOAD_THUMBNAILS_PATH', UPLOADS_PATH . '/thumbnails');

// Image processing settings
define('THUMBNAIL_MAX_WIDTH', 400);
define('PRODUCT_IMAGE_MAX_WIDTH', 600);
define('CATEGORY_BACKGROUND_WIDTH', 1600);
define('CATEGORY_BACKGROUND_HEIGHT', 650);

// Security settings
define('SESSION_LIFETIME', 1440); // 24 minutes in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes in seconds
define('CSRF_TOKEN_LIFETIME', 1800); // 30 minutes

// Pagination settings
define('DEFAULT_ITEMS_PER_PAGE', 10);
define('MAX_ITEMS_PER_PAGE', 50);

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hour

// Module positions
define('MODULE_POSITIONS', [
    'top-module-1' => 'Top Module 1',
    'top-module-2' => 'Top Module 2', 
    'top-module-3' => 'Top Module 3',
    'top-module-4' => 'Top Module 4',
    'bottom-module-1' => 'Bottom Module 1',
    'bottom-module-2' => 'Bottom Module 2',
    'bottom-module-3' => 'Bottom Module 3', 
    'bottom-module-4' => 'Bottom Module 4',
    'footer-module-1' => 'Footer Module 1',
    'footer-module-2' => 'Footer Module 2',
    'footer-module-3' => 'Footer Module 3',
    'footer-module-4' => 'Footer Module 4'
]);

// Module types
define('MODULE_TYPES', [
    'rich_text' => 'Rich Text',
    'custom_html' => 'Custom HTML',
    'image' => 'Image',
    'menu' => 'Menu'
]);

// Default theme colors
define('DEFAULT_THEME', [
    'full_bg_color' => '#1a269b',
    'h1_color' => '#ffcc3f',
    'h2_color' => '#ffcc3f', 
    'h3_color' => '#ffffff',
    'h4_color' => '#ffffff',
    'h5_color' => '#ffffff',
    'links_color' => '#0066cc',
    'footer_bg_color' => '#000000'
]);

// Email configuration
define('MAIL_FROM_NAME', 'CMS System');
define('MAIL_FROM_EMAIL', 'noreply@yoursite.com');

// Create required directories if they don't exist
$requiredDirs = [
    UPLOADS_PATH,
    UPLOAD_IMAGES_PATH, 
    UPLOAD_THUMBNAILS_PATH,
    LOGS_PATH
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Autoload function for classes
spl_autoload_register(function ($className) {
    $possiblePaths = [
        INCLUDES_PATH . '/classes/' . $className . '.php',
        INCLUDES_PATH . '/' . $className . '.php',
        CONFIG_PATH . '/' . $className . '.php'
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
});

// Include core files
require_once CONFIG_PATH . '/database.php';

/**
 * Utility Functions
 */

/**
 * Redirect with message
 */
function redirect($url, $message = '', $type = 'info') {
    SecureSession::start();
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    SecureSession::start();
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Secure file upload function
 */
function uploadImage($file, $destination, $maxWidth = null) {
    try {
        InputValidator::validateFileUpload($file, ALLOWED_IMAGE_TYPES, MAX_FILE_SIZE);
        
        $filename = uniqid() . '_' . basename($file['name']);
        $filepath = $destination . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception("Failed to upload file");
        }
        
        // Resize image if maxWidth specified
        if ($maxWidth) {
            resizeImage($filepath, $maxWidth);
        }
        
        return $filename;
        
    } catch (Exception $e) {
        ErrorHandler::logError("File upload error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Resize image maintaining aspect ratio
 */
function resizeImage($filepath, $maxWidth) {
    $imageInfo = getimagesize($filepath);
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];
    
    if ($width <= $maxWidth) {
        return; // No resize needed
    }
    
    $newWidth = $maxWidth;
    $newHeight = ($height * $newWidth) / $width;
    
    // Create image resource based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filepath);
            break;
        default:
            throw new Exception("Unsupported image type");
    }
    
    // Create new image
    $destination = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save resized image
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($destination, $filepath, 85);
            break;
    }
    
    // Clean up memory
    imagedestroy($source);
    imagedestroy($destination);
}

/**
 * Generate thumbnail
 */
function generateThumbnail($sourceFile, $destinationPath, $maxWidth = THUMBNAIL_MAX_WIDTH) {
    $pathInfo = pathinfo($sourceFile);
    $thumbnailName = $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
    $thumbnailPath = $destinationPath . '/' . $thumbnailName;
    
    copy($sourceFile, $thumbnailPath);
    resizeImage($thumbnailPath, $maxWidth);
    
    return $thumbnailName;
}

/**
 * Cache management functions
 */
function getCacheKey($key) {
    return md5($key);
}

function getCache($key) {
    if (!CACHE_ENABLED) return false;
    
    $cacheFile = LOGS_PATH . '/cache_' . getCacheKey($key) . '.tmp';
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_LIFETIME) {
        return unserialize(file_get_contents($cacheFile));
    }
    
    return false;
}

function setCache($key, $data) {
    if (!CACHE_ENABLED) return false;
    
    $cacheFile = LOGS_PATH . '/cache_' . getCacheKey($key) . '.tmp';
    file_put_contents($cacheFile, serialize($data));
}

function clearCache($pattern = '') {
    $files = glob(LOGS_PATH . '/cache_' . $pattern . '*.tmp');
    foreach ($files as $file) {
        unlink($file);
    }
}

/**
 * Format bytes to human readable format
 */
function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

/**
 * Sanitize output for display
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Current timestamp for database
 */
function now() {
    return date('Y-m-d H:i:s');
}
?>