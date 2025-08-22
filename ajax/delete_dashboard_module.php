<?php
/**
 * Delete Dashboard Module AJAX Handler
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
        throw new Exception('Module ID is required');
    }
    
    $moduleId = InputValidator::validateInteger($data['module_id'], 1, null, true);
    
    $db = DB::getInstance();
    
    // Get module info before deletion
    $stmt = $db->prepare("SELECT module_name, module_title FROM dashboard_modules WHERE id = ?");
    $stmt->execute([$moduleId]);
    $module = $stmt->fetch();
    
    if (!$module) {
        throw new Exception('Dashboard module not found');
    }
    
    // Prevent deletion of core modules (optional protection)
    $coreModules = ['total_products', 'active_modules', 'total_categories'];
    if (in_array($module['module_name'], $coreModules)) {
        throw new Exception('Cannot delete core dashboard modules');
    }
    
    // Delete the module
    $stmt = $db->prepare("DELETE FROM dashboard_modules WHERE id = ?");
    $stmt->execute([$moduleId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Module not found or already deleted');
    }
    
    // Reorder remaining modules
    $stmt = $db->prepare("
        UPDATE dashboard_modules 
        SET display_order = (
            SELECT COUNT(*) 
            FROM (SELECT id FROM dashboard_modules WHERE id <= dashboard_modules.id) AS subq
        )
        ORDER BY display_order
    ");
    $stmt->execute();
    
    // Log the deletion
    ErrorHandler::logError("Dashboard module deleted", [
        'module_id' => $moduleId,
        'module_name' => $module['module_name'],
        'module_title' => $module['module_title'],
        'user_id' => $_SESSION['user_id'] ?? 'unknown'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Dashboard module "' . $module['module_title'] . '" deleted successfully'
    ]);
    
} catch (Exception $e) {
    ErrorHandler::logError("Delete dashboard module failed: " . $e->getMessage(), [
        'module_id' => $data['module_id'] ?? 'unknown'
    ]);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>