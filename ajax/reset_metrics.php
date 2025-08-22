<?php
/**
 * Reset Dashboard Metrics AJAX Handler
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
    $db = DB::getInstance();
    
    // Begin transaction
    $db->beginTransaction();
    
    // Clear visitor statistics
    $stmt = $db->prepare("DELETE FROM visitor_stats");
    $stmt->execute();
    $visitorCount = $stmt->rowCount();
    
    // Clear click statistics
    $stmt = $db->prepare("DELETE FROM click_stats");
    $stmt->execute();
    $clickCount = $stmt->rowCount();
    
    // Clear login attempts (optional - for security metrics)
    $stmt = $db->prepare("DELETE FROM login_attempts");
    $stmt->execute();
    $attemptCount = $stmt->rowCount();
    
    // Commit transaction
    $db->commit();
    
    // Log the reset action
    ErrorHandler::logError("Dashboard metrics reset by admin", [
        'user_id' => $_SESSION['user_id'] ?? 'unknown',
        'visitor_records_deleted' => $visitorCount,
        'click_records_deleted' => $clickCount,
        'login_attempts_deleted' => $attemptCount
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'All dashboard metrics have been reset successfully',
        'details' => [
            'visitor_records_deleted' => $visitorCount,
            'click_records_deleted' => $clickCount,
            'login_attempts_deleted' => $attemptCount
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    if ($db->inTransaction()) {
        $db->rollback();
    }
    
    ErrorHandler::logError("Reset metrics failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to reset metrics: ' . $e->getMessage()
    ]);
}
?>