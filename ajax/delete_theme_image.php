<?php
/**
 * Delete Theme Image AJAX Handler
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
    
    if (!$data || !isset($data['image_type'])) {
        throw new Exception('Invalid request data');
    }
    
    $imageType = $data['image_type'];
    $allowedTypes = ['full_bg_image', 'store_logo', 'login_logo', 'backend_logo'];
    
    if (!in_array($imageType, $allowedTypes)) {
        throw new Exception('Invalid image type');
    }
    
    $db = DB::getInstance();
    
    // Get current theme settings
    $stmt = $db->prepare("SELECT * FROM theme_settings LIMIT 1");
    $stmt->execute();
    $theme = $stmt->fetch();
    
    if (!$theme) {
        throw new Exception('Theme settings not found');
    }
    
    $currentImage = $theme[$imageType];
    
    if ($currentImage) {
        // Determine the correct path based on image type
        if ($imageType === 'full_bg_image') {
            $imagePath = UPLOAD_IMAGES_PATH . '/' . $currentImage;
            $thumbPath = UPLOAD_THUMBNAILS_PATH . '/' . pathinfo($currentImage, PATHINFO_FILENAME) . '_thumb.' . pathinfo($currentImage, PATHINFO_EXTENSION);
        } else {
            // Logo images
            $imagePath = UPLOADS_PATH . '/logos/' . $currentImage;
            $thumbPath = null; // Logos don't have thumbnails
        }
        
        // Delete image files
        @unlink($imagePath);
        if ($thumbPath) {
            @unlink($thumbPath);
        }
        
        // Update database
        $stmt = $db->prepare("UPDATE theme_settings SET {$imageType} = NULL WHERE id = 1");
        $stmt->execute();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Theme image deleted successfully'
    ]);
    
} catch (Exception $e) {
    ErrorHandler::logError("Delete theme image failed: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>