<?php
/**
 * Delete Category Background Image AJAX Handler
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
    
    if (!$data || !isset($data['category_id'])) {
        throw new Exception('Invalid request data');
    }
    
    $categoryId = InputValidator::validateInteger($data['category_id'], 1, null, true);
    
    $db = DB::getInstance();
    
    // Get current category image
    $stmt = $db->prepare("SELECT background_image FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();
    
    if (!$category) {
        throw new Exception('Category not found');
    }
    
    if ($category['background_image']) {
        // Delete image files
        @unlink(UPLOAD_IMAGES_PATH . '/' . $category['background_image']);
        @unlink(UPLOAD_THUMBNAILS_PATH . '/' . pathinfo($category['background_image'], PATHINFO_FILENAME) . '_thumb.' . pathinfo($category['background_image'], PATHINFO_EXTENSION));
        
        // Update database
        $stmt = $db->prepare("UPDATE categories SET background_image = NULL WHERE id = ?");
        $stmt->execute([$categoryId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Category background image deleted successfully'
    ]);
    
} catch (Exception $e) {
    ErrorHandler::logError("Delete category image failed: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>