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
    
    $metric_type = filter_input(INPUT_POST, 'metric_type', FILTER_SANITIZE_STRING);
    
    $allowed_types = ['all', 'visitors', 'clicks', 'events', 'performance'];
    
    if (!in_array($metric_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid metric type']);
        exit;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        switch ($metric_type) {
            case 'all':
                // Reset all analytics tables
                $conn->query("TRUNCATE TABLE visitor_stats");
                $conn->query("TRUNCATE TABLE click_tracking");
                $conn->query("TRUNCATE TABLE event_tracking");
                $conn->query("TRUNCATE TABLE performance_metrics");
                $message = 'All metrics have been reset successfully';
                break;
                
            case 'visitors':
                $conn->query("TRUNCATE TABLE visitor_stats");
                $message = 'Visitor statistics have been reset successfully';
                break;
                
            case 'clicks':
                $conn->query("TRUNCATE TABLE click_tracking");
                $message = 'Click tracking data has been reset successfully';
                break;
                
            case 'events':
                $conn->query("TRUNCATE TABLE event_tracking");
                $message = 'Event tracking data has been reset successfully';
                break;
                
            case 'performance':
                $conn->query("TRUNCATE TABLE performance_metrics");
                $message = 'Performance metrics have been reset successfully';
                break;
        }
        
        // Commit transaction
        $conn->commit();
        
        // Log the action
        $log_message = "Admin reset metrics: " . $metric_type;
        error_log($log_message);
        
        echo json_encode(['success' => true, 'message' => $message]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Reset metrics error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred while resetting metrics']);
}
?>