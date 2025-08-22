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
    
    // Define default theme settings
    $default_settings = [
        // Typography
        'primary_font' => 'Arial, sans-serif',
        'secondary_font' => 'Georgia, serif',
        'font_size_base' => '16',
        'font_size_h1' => '32',
        'font_size_h2' => '24',
        'font_size_h3' => '20',
        
        // Colors
        'primary_color' => '#007bff',
        'secondary_color' => '#6c757d',
        'accent_color' => '#28a745',
        'background_color' => '#ffffff',
        'text_color' => '#333333',
        'link_color' => '#007bff',
        'header_bg_color' => '#ffffff',
        'footer_bg_color' => '#f8f9fa',
        
        // Layout & Spacing
        'container_max_width' => '1200',
        'header_padding' => '20',
        'content_padding' => '30',
        'footer_padding' => '40',
        'section_margin' => '60',
        'border_radius' => '5',
        
        // Header & Navigation
        'header_height' => '80',
        'nav_menu_style' => 'horizontal',
        'sticky_header' => '1',
        'show_search' => '1',
        
        // Footer
        'footer_style' => 'simple',
        'show_social_links' => '1',
        'copyright_text' => '© 2025 Your Company Name. All rights reserved.',
        
        // Images (reset to empty - no default images)
        'site_logo' => '',
        'favicon' => '',
        'footer_logo' => '',
        
        // Advanced
        'custom_css' => '',
        'google_fonts' => '',
        'animation_speed' => '300',
        'mobile_breakpoint' => '768'
    ];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get current logo files for deletion
        $logo_query = "SELECT setting_name, setting_value FROM site_settings WHERE setting_name IN ('site_logo', 'favicon', 'footer_logo')";
        $result = $conn->query($logo_query);
        
        $current_logos = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['setting_value'])) {
                $current_logos[] = $row['setting_value'];
            }
        }
        
        // Delete current logo files
        $uploads_dir = dirname(__DIR__) . '/uploads/logos/';
        $thumbnails_dir = dirname(__DIR__) . '/uploads/thumbnails/';
        
        foreach ($current_logos as $logo_file) {
            $image_path = $uploads_dir . $logo_file;
            $thumbnail_path = $thumbnails_dir . 'thumb_' . $logo_file;
            
            if (file_exists($image_path)) {
                unlink($image_path);
            }
            if (file_exists($thumbnail_path)) {
                unlink($thumbnail_path);
            }
        }
        
        // Update all theme settings to defaults
        $stmt = $conn->prepare("INSERT INTO site_settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        foreach ($default_settings as $setting_name => $setting_value) {
            $stmt->bind_param("sss", $setting_name, $setting_value, $setting_value);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Log the action
        $log_message = "Admin reset theme settings to defaults";
        error_log($log_message);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Theme settings have been reset to defaults successfully',
            'reload_required' => true
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Reset theme defaults error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred while resetting theme settings']);
}
?>