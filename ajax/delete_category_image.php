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
    
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    
    if (!$category_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
        exit;
    }
    
    // Get current image path
    $stmt = $conn->prepare("SELECT background_image FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    
    if (!$category) {
        echo json_encode(['success' => false, 'message' => 'Category not found']);
        exit;
    }
    
    // Delete physical files
    $uploads_dir = dirname(__DIR__) . '/uploads/images/';
    $thumbnails_dir = dirname(__DIR__) . '/uploads/thumbnails/';
    
    if (!empty($category['background_image'])) {
        $image_path = $uploads_dir . $category['background_image'];
        $thumbnail_path = $thumbnails_dir . 'thumb_' . $category['background_image'];
        
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        if (file_exists($thumbnail_path)) {
            unlink($thumbnail_path);
        }
    }
    
    // Update database
    $stmt = $conn->prepare("UPDATE categories SET background_image = NULL WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Category image deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    error_log("Delete category image error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>