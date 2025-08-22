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
    
    $test_email = filter_input(INPUT_POST, 'test_email', FILTER_VALIDATE_EMAIL);
    
    if (!$test_email) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }
    
    // Get email server settings
    $settings_query = "SELECT setting_name, setting_value FROM site_settings WHERE setting_name IN ('smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_secure', 'smtp_from_email', 'smtp_from_name')";
    $result = $conn->query($settings_query);
    
    $email_settings = [];
    while ($row = $result->fetch_assoc()) {
        $email_settings[$row['setting_name']] = $row['setting_value'];
    }
    
    // Check if email settings are configured
    if (empty($email_settings['smtp_host']) || empty($email_settings['smtp_username'])) {
        echo json_encode(['success' => false, 'message' => 'Email server settings are not configured']);
        exit;
    }
    
    // Prepare test email
    $subject = 'CMS Test Email - ' . date('Y-m-d H:i:s');
    $message = "
    <html>
    <head>
        <title>CMS Test Email</title>
    </head>
    <body>
        <h2>Email Configuration Test</h2>
        <p>This is a test email from your CMS system.</p>
        <p><strong>Test Details:</strong></p>
        <ul>
            <li>Sent at: " . date('Y-m-d H:i:s') . "</li>
            <li>SMTP Host: " . htmlspecialchars($email_settings['smtp_host']) . "</li>
            <li>SMTP Port: " . htmlspecialchars($email_settings['smtp_port']) . "</li>
            <li>From Email: " . htmlspecialchars($email_settings['smtp_from_email']) . "</li>
        </ul>
        <p>If you received this email, your email configuration is working correctly!</p>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = array(
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $email_settings['smtp_from_name'] . ' <' . $email_settings['smtp_from_email'] . '>',
        'Reply-To: ' . $email_settings['smtp_from_email'],
        'X-Mailer: PHP/' . phpversion()
    );
    
    // Try different email sending methods based on configuration
    $email_sent = false;
    $error_message = '';
    
    // Method 1: Try PHPMailer if configured
    if (!empty($email_settings['smtp_password'])) {
        try {
            // Configure SMTP settings
            ini_set('SMTP', $email_settings['smtp_host']);
            ini_set('smtp_port', $email_settings['smtp_port']);
            
            // For production, you would use PHPMailer here
            // This is a simplified version using mail() function
            $email_sent = mail($test_email, $subject, $message, implode("\r\n", $headers));
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
    
    // Method 2: Fallback to basic mail() function
    if (!$email_sent) {
        try {
            $email_sent = mail($test_email, $subject, $message, implode("\r\n", $headers));
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
    
    if ($email_sent) {
        // Log successful test
        $log_message = "Test email sent successfully to: " . $test_email;
        error_log($log_message);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Test email sent successfully to ' . $test_email
        ]);
    } else {
        // Log failure
        $log_message = "Test email failed to send to: " . $test_email . " - Error: " . $error_message;
        error_log($log_message);
        
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to send test email. Check your email server settings and server logs.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Send test email error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred while sending test email']);
}
?>