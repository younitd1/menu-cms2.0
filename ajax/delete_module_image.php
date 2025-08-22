<?php
/**
 * Delete Module Background Image AJAX Handler
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
    
    if (!$data || !isset($data['module_id'])) {
        throw new Exception('Invalid request data');
    }
    
    $moduleId = InputValidator::validateInteger($data['module_id'], 1, null, true);
    
    $db = DB::getInstance();
    
    // Get current module image
    $stmt = $db->prepare("SELECT background_image FROM modules WHERE id = ?");
    $stmt->execute([$moduleId]);
    $module = $stmt->fetch();
    
    if (!$module) {
        throw new Exception('Module not found');
    }
    
    if ($module['background_image']) {
        // Delete image files
        @unlink(UPLOAD_IMAGES_PATH . '/' . $module['background_image']);
        @unlink(UPLOAD_THUMBNAILS_PATH . '/' . pathinfo($module['background_image'], PATHINFO_FILENAME) . '_thumb.' . pathinfo($module['background_image'], PATHINFO_EXTENSION));
        
        // Update database
        $stmt = $db->prepare("UPDATE modules SET background_image = NULL WHERE id = ?");
        $stmt->execute([$moduleId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Module background image deleted successfully'
    ]);
    
} catch (Exception $e) {
    ErrorHandler::logError("Delete module image failed: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>