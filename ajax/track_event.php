<?php
/**
 * General Event Tracking AJAX Handler
 * Records various frontend events for analytics
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
    
    if (!$data || !isset($data['event_type'])) {
        throw new Exception('Invalid request data');
    }
    
    $eventType = $data['event_type'];
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Validate event type
    $allowedEvents = [
        'page_view',
        'navigation_click', 
        'time_on_page',
        'scroll_depth',
        'page_performance'
    ];
    
    if (!in_array($eventType, $allowedEvents)) {
        throw new Exception('Invalid event type');
    }
    
    $db = DB::getInstance();
    
    // Handle different event types
    switch ($eventType) {
        case 'page_view':
            $pageTitle = InputValidator::validateText($data['page_title'] ?? '', 255, false);
            $referrer = InputValidator::validateText($data['referrer'] ?? '', 255, false);
            $url = InputValidator::validateText($data['url'] ?? '', 255, false);
            
            // Record page view in visitor_stats
            $stmt = $db->prepare("
                INSERT INTO visitor_stats (ip_address, user_agent, page_visited, visit_date) 
                VALUES (?, ?, ?, CURDATE())
            ");
            $stmt->execute([$ipAddress, $userAgent, $url]);
            break;
            
        case 'navigation_click':
            $category = InputValidator::validateText($data['category'] ?? '', 100, false);
            $linkText = InputValidator::validateText($data['link_text'] ?? '', 100, false);
            $menuType = InputValidator::validateText($data['menu_type'] ?? '', 20, false);
            
            // Could create a navigation_clicks table for detailed analysis
            // For now, log as a general event
            ErrorHandler::logError("Navigation click: {$linkText} ({$category}) via {$menuType}", [
                'ip' => $ipAddress,
                'category' => $category,
                'menu_type' => $menuType
            ]);
            break;
            
        case 'time_on_page':
            $duration = InputValidator::validateInteger($data['duration'] ?? 0, 0);
            
            // Log time on page (could be stored in a separate table)
            ErrorHandler::logError("Time on page: {$duration}ms", [
                'ip' => $ipAddress,
                'duration' => $duration
            ]);
            break;
            
        case 'scroll_depth':
            $maxScrollPercent = InputValidator::validateInteger($data['max_scroll_percent'] ?? 0, 0, 100);
            
            // Log scroll depth
            ErrorHandler::logError("Scroll depth: {$maxScrollPercent}%", [
                'ip' => $ipAddress,
                'scroll_percent' => $maxScrollPercent
            ]);
            break;
            
        case 'page_performance':
            $loadTime = InputValidator::validateInteger($data['load_time'] ?? 0, 0);
            $domReady = InputValidator::validateInteger($data['dom_ready'] ?? 0, 0);
            
            // Log performance metrics
            ErrorHandler::logError("Page performance - Load: {$loadTime}ms, DOM: {$domReady}ms", [
                'ip' => $ipAddress,
                'load_time' => $loadTime,
                'dom_ready' => $domReady
            ]);
            break;
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Event tracked successfully',
        'event_type' => $eventType,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Log error
    ErrorHandler::logError("Event tracking failed: " . $e->getMessage(), [
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