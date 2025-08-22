<?php
/**
 * Click Tracking AJAX Handler
 * Records clicks for analytics (Order Now, Phone Number, Module clicks)
 */

require_once '../config/config.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['click_type'])) {
        throw new Exception('Invalid request data');
    }
    
    $clickType = $data['click_type'];
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Validate click type
    $allowedTypes = ['order_now', 'phone_number', 'module_click'];
    if (!in_array($clickType, $allowedTypes)) {
        throw new Exception('Invalid click type');
    }
    
    $db = DB::getInstance();
    
    // Record the click
    if ($clickType === 'module_click') {
        // Module click tracking
        $moduleId = InputValidator::validateInteger($data['module_id'] ?? 0, 1);
        
        // Record in a separate module clicks table or extend click_stats
        $stmt = $db->prepare("
            INSERT INTO click_stats (click_type, ip_address, click_date, click_time) 
            VALUES (?, ?, CURDATE(), NOW())
        ");
        $stmt->execute(['module_click', $ipAddress]);
        
        // You could also create a separate module_clicks table for more detailed tracking
        /*
        $stmt = $db->prepare("
            INSERT INTO module_clicks (module_id, ip_address, click_date, click_time) 
            VALUES (?, ?, CURDATE(), NOW())
        ");
        $stmt->execute([$moduleId, $ipAddress]);
        */
        
    } else {
        // Standard click tracking (order_now, phone_number)
        $stmt = $db->prepare("
            INSERT INTO click_stats (click_type, ip_address, click_date, click_time) 
            VALUES (?, ?, CURDATE(), NOW())
        ");
        $stmt->execute([$clickType, $ipAddress]);
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Click tracked successfully',
        'click_type' => $clickType,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Log error
    ErrorHandler::logError("Click tracking failed: " . $e->getMessage(), [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'data' => $input ?? 'no input'
    ]);
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>