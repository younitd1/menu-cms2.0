<?php
/**
 * Send Test Email AJAX Handler
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
    
    if (!$data || !isset($data['test_email'])) {
        throw new Exception('Test email address is required');
    }
    
    $testEmail = InputValidator::validateEmail($data['test_email'], true);
    
    $db = DB::getInstance();
    
    // Get email settings
    $stmt = $db->prepare("SELECT * FROM email_settings LIMIT 1");
    $stmt->execute();
    $emailSettings = $stmt->fetch();
    
    if (!$emailSettings || empty($emailSettings['smtp_host'])) {
        throw new Exception('Email settings are not configured');
    }
    
    // Get site settings
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_group = 'site'");
    $stmt->execute();
    $siteSettings = [];
    while ($row = $stmt->fetch()) {
        $siteSettings[$row['setting_key']] = $row['setting_value'];
    }
    
    $siteName = $siteSettings['site_name'] ?? 'CMS Website';
    
    // Prepare test email content
    $subject = "Test Email from {$siteName}";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1a269b; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Email Configuration Test</h2>
            </div>
            <div class='content'>
                <p>Hello!</p>
                <p>This is a test email from <strong>{$siteName}</strong> to verify your email configuration is working correctly.</p>
                <p><strong>Test Details:</strong></p>
                <ul>
                    <li>Sent at: " . date('Y-m-d H:i:s') . "</li>
                    <li>From: {$emailSettings['from_email']}</li>
                    <li>SMTP Host: {$emailSettings['smtp_host']}</li>
                    <li>SMTP Port: {$emailSettings['smtp_port']}</li>
                </ul>
                <p>If you received this email, your SMTP configuration is working properly!</p>
            </div>
            <div class='footer'>
                <p>This is an automated test email from {$siteName}</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send email using PHP's mail function (basic implementation)
    // In production, you might want to use PHPMailer or similar library
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $emailSettings['from_email'],
        'Reply-To: ' . $emailSettings['from_email'],
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $success = mail($testEmail, $subject, $message, implode("\r\n", $headers));
    
    if (!$success) {
        throw new Exception('Failed to send email. Please check your server\'s mail configuration.');
    }
    
    // Log the test
    ErrorHandler::logError("Test email sent successfully", [
        'recipient' => $testEmail,
        'smtp_host' => $emailSettings['smtp_host'],
        'user_id' => $_SESSION['user_id'] ?? 'unknown'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Test email sent successfully to ' . $testEmail
    ]);
    
} catch (Exception $e) {
    ErrorHandler::logError("Send test email failed: " . $e->getMessage(), [
        'recipient' => $data['test_email'] ?? 'unknown'
    ]);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>