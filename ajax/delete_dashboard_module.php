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
    
    $module_name = filter_input(INPUT_POST, 'module_name', FILTER_SANITIZE_STRING);
    
    // Define allowed dashboard modules
    $allowed_modules = [
        'visitor_stats',
        'click_tracking', 
        'performance_metrics',
        'recent_activity',
        'system_status',
        'quick_stats'
    ];
    
    if (!in_array($module_name, $allowed_modules)) {
        echo json_encode(['success' => false, 'message' => 'Invalid dashboard module']);
        exit;
    }
    
    // Get current dashboard configuration
    $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_name = 'dashboard_modules'");
    $stmt->execute();
    $result = $stmt->get_result();
    $setting = $result->fetch_assoc();
    
    $dashboard_modules = [];
    if ($setting && !empty($setting['setting_value'])) {
        $dashboard_modules = json_decode($setting['setting_value'], true);
    } else {
        // Default dashboard modules if none set
        $dashboard_modules = [
            'visitor_stats' => ['enabled' => true, 'order' => 1],
            'click_tracking' => ['enabled' => true, 'order' => 2],
            'performance_metrics' => ['enabled' => true, 'order' => 3],
            'recent_activity' => ['enabled' => true, 'order' => 4],
            'system_status' => ['enabled' => true, 'order' => 5],
            'quick_stats' => ['enabled' => true, 'order' => 6]
        ];
    }
    
    // Disable the specified module
    if (isset($dashboard_modules[$module_name])) {
        $dashboard_modules[$module_name]['enabled'] = false;
    }
    
    // Update database
    $new_modules_json = json_encode($dashboard_modules);
    $stmt = $conn->prepare("INSERT INTO site_settings (setting_name, setting_value) VALUES ('dashboard_modules', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("ss", $new_modules_json, $new_modules_json);
    
    if ($stmt->execute()) {
        $module_titles = [
            'visitor_stats' => 'Visitor Statistics',
            'click_tracking' => 'Click Tracking',
            'performance_metrics' => 'Performance Metrics',
            'recent_activity' => 'Recent Activity',
            'system_status' => 'System Status',
            'quick_stats' => 'Quick Statistics'
        ];
        
        $module_title = isset($module_titles[$module_name]) ? $module_titles[$module_name] : $module_name;
        
        echo json_encode([
            'success' => true, 
            'message' => $module_title . ' module disabled successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    error_log("Delete dashboard module error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>