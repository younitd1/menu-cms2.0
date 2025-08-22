<?php
/**
 * Delete Product Image AJAX Handler
 */

require_once '../config/config.php';
SecureSession::requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['product_id'])) {
        throw new Exception('Invalid request data');
    }
    
    $productId = InputValidator::validateInteger($data['product_id'], 1, null, true);
    
    $db = DB::getInstance();
    
    // Get current product image
    $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    if ($product['image']) {
        // Delete image files
        @unlink(UPLOAD_IMAGES_PATH . '/' . $product['image']);
        @unlink(UPLOAD_THUMBNAILS_PATH . '/' . pathinfo($product['image'], PATHINFO_FILENAME) . '_thumb.' . pathinfo($product['image'], PATHINFO_EXTENSION));
        
        // Update database
        $stmt = $db->prepare("UPDATE products SET image = NULL, image_alignment = 'none' WHERE id = ?");
        $stmt->execute([$productId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Product image deleted successfully'
    ]);
    
} catch (Exception $e) {
    ErrorHandler::logError("Delete product image failed: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>