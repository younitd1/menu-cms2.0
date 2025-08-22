<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Security checks
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $image_type = filter_input(INPUT_POST, 'image_type', FILTER_SANITIZE_STRING);
    
    $allowed_types = ['site_logo', 'favicon', 'footer_logo'];
    
    if (!in_array($image_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type']);
        exit;
    }
    
    // Get current theme settings
    $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_name = ?");
    $stmt->bind_param("s", $image_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $setting = $result->fetch_assoc();
    
    if (!$setting) {
        echo json_encode(['success' => false, 'message' => 'Setting not found']);
        exit;
    }
    
    // Delete physical files
    $uploads_dir = dirname(__DIR__) . '/uploads/logos/';
    $thumbnails_dir = dirname(__DIR__) . '/uploads/thumbnails/';
    
    if (!empty($setting['setting_value'])) {
        $image_path = $uploads_dir . $setting['setting_value'];
        $thumbnail_path = $thumbnails_dir . 'thumb_' . $setting['setting_value'];
        
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        if (file_exists($thumbnail_path)) {
            unlink($thumbnail_path);
        }
    }
    
    // Update database - set to empty value
    $stmt = $conn->prepare("UPDATE site_settings SET setting_value = '' WHERE setting_name = ?");
    $stmt->bind_param("s", $image_type);
    
    if ($stmt->execute()) {
        $message = '';
        switch ($image_type) {
            case 'site_logo':
                $message = 'Site logo deleted successfully';
                break;
            case 'favicon':
                $message = 'Favicon deleted successfully';
                break;
            case 'footer_logo':
                $message = 'Footer logo deleted successfully';
                break;
        }
        
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    error_log("Delete theme image error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>