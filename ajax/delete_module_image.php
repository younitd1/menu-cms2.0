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
    
    $module_id = filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);
    $image_index = filter_input(INPUT_POST, 'image_index', FILTER_VALIDATE_INT);
    
    if (!$module_id || $image_index === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    // Get current module data
    $stmt = $conn->prepare("SELECT content_data FROM modules WHERE id = ? AND type = 'image'");
    $stmt->bind_param("i", $module_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $module = $result->fetch_assoc();
    
    if (!$module) {
        echo json_encode(['success' => false, 'message' => 'Module not found']);
        exit;
    }
    
    $content_data = json_decode($module['content_data'], true);
    
    if (!isset($content_data['images'][$image_index])) {
        echo json_encode(['success' => false, 'message' => 'Image not found in module']);
        exit;
    }
    
    // Delete physical files
    $uploads_dir = dirname(__DIR__) . '/uploads/images/';
    $thumbnails_dir = dirname(__DIR__) . '/uploads/thumbnails/';
    
    $image_filename = $content_data['images'][$image_index]['filename'];
    $image_path = $uploads_dir . $image_filename;
    $thumbnail_path = $thumbnails_dir . 'thumb_' . $image_filename;
    
    if (file_exists($image_path)) {
        unlink($image_path);
    }
    if (file_exists($thumbnail_path)) {
        unlink($thumbnail_path);
    }
    
    // Remove from content data
    array_splice($content_data['images'], $image_index, 1);
    
    // Reindex array to maintain sequential indices
    $content_data['images'] = array_values($content_data['images']);
    
    // Update database
    $new_content_data = json_encode($content_data);
    $stmt = $conn->prepare("UPDATE modules SET content_data = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $new_content_data, $module_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Image deleted successfully',
            'remaining_images' => count($content_data['images'])
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    error_log("Delete module image error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>