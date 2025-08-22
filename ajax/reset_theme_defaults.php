<?php
/**
 * Reset Theme to Defaults AJAX Handler
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
    
    // Get current theme settings to preserve logos
    $stmt = $db->prepare("SELECT store_logo, login_logo, backend_logo FROM theme_settings LIMIT 1");
    $stmt->execute();
    $currentTheme = $stmt->fetch();
    
    // Reset theme settings to defaults while preserving logos
    $stmt = $db->prepare("
        UPDATE theme_settings SET 
            full_bg_image = NULL,
            full_bg_color = '#1a269b',
            main_font = 'Arial, sans-serif',
            h1_font = 'Arial, sans-serif',
            h1_color = '#ffcc3f',
            h1_size = 32,
            h2_font = 'Arial, sans-serif', 
            h2_color = '#ffcc3f',
            h2_size = 28,
            h3_font = 'Arial, sans-serif',
            h3_color = '#ffffff', 
            h3_size = 24,
            h4_font = 'Arial, sans-serif',
            h4_color = '#ffffff',
            h4_size = 20,
            h5_font = 'Arial, sans-serif',
            h5_color = '#ffffff',
            h5_size = 16,
            links_color = '#0066cc',
            product_margin = 20,
            product_padding = 15,
            category_margin = 20,
            category_padding = 15,
            updated_at = NOW()
        WHERE id = 1
    ");
    $stmt->execute();
    
    // Clear theme cache
    clearCache('theme_*');
    
    // Log the reset action
    ErrorHandler::logError("Theme settings reset to defaults", [
        'user_id' => $_SESSION['user_id'] ?? 'unknown',
        'logos_preserved' => [
            'store_logo' => $currentTheme['store_logo'] ?? null,
            'login_logo' => $currentTheme['login_logo'] ?? null,
            'backend_logo' => $currentTheme['backend_logo'] ?? null
        ]
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Theme settings have been reset to defaults successfully',
        'note' => 'Your uploaded logos have been preserved'
    ]);
    
} catch (Exception $e) {
    ErrorHandler::logError("Reset theme defaults failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to reset theme settings: ' . $e->getMessage()
    ]);
}
?>