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
    
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    
    if (!$product_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    // Get current image path
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Delete physical files
    $uploads_dir = dirname(__DIR__) . '/uploads/images/';
    $thumbnails_dir = dirname(__DIR__) . '/uploads/thumbnails/';
    
    if (!empty($product['image'])) {
        $image_path = $uploads_dir . $product['image'];
        $thumbnail_path = $thumbnails_dir . 'thumb_' . $product['image'];
        
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        if (file_exists($thumbnail_path)) {
            unlink($thumbnail_path);
        }
    }
    
    // Update database
    $stmt = $conn->prepare("UPDATE products SET image = NULL WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    error_log("Delete product image error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>