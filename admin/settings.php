<?php
require_once '../config/config.php';
SecureSession::requireLogin();

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

$activeTab = $_GET['tab'] ?? 'site';
$message = '';
$messageType = 'info';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!CSRFProtection::validateToken($_POST['csrf_token'] ?? '')) {
            throw new Exception("Invalid request. Please try again.");
        }
        
        switch ($activeTab) {
            case 'site':
                updateSiteSettings($_POST);
                $message = 'Site settings updated successfully!';
                break;
                
            case 'user':
                updateUserSettings($_POST, $currentUser['id']);
                $message = 'User settings updated successfully!';
                break;
                
            case 'email':
                updateEmailSettings($_POST);
                $message = 'Email settings updated successfully!';
                break;
                
            case 'security':
                updateSecuritySettings($_POST);
                $message = 'Security settings updated successfully!';
                break;
                
            case 'dashboard':
                updateDashboardSettings($_POST);
                $message = 'Dashboard settings updated successfully!';
                break;
        }
        
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get settings data
$siteSettings = getSiteSettings();
$securitySettings = getSecuritySettings();
$emailSettings = getEmailSettings();
$dashboardModules = getDashboardModules();

$flashMessage = getFlashMessage();
if (!$flashMessage && $message) {
    $flashMessage = ['message' => $message, 'type' => $messageType];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - CMS Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="<?= e(CSRFProtection::generateToken()) ?>">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content-area">
                <div class="page-header">
                    <h1 class="page-title">Settings</h1>
                </div>
                
                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?= e($flashMessage['type']) ?>">
                        <?= e($flashMessage['message']) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Settings Tabs -->
                <div class="settings-tabs">
                    <div class="tab-navigation">
                        <a href="?tab=site" class="tab-link <?= $activeTab === 'site' ? 'active' : '' ?>">
                            <i class="fas fa-globe"></i> Site Settings
                        </a>
                        <a href="?tab=user" class="tab-link <?= $activeTab === 'user' ? 'active' : '' ?>">
                            <i class="fas fa-user"></i> User Profile
                        </a>
                        <a href="?tab=email" class="tab-link <?= $activeTab === 'email' ? 'active' : '' ?>">
                            <i class="fas fa-envelope"></i> Email Server
                        </a>
                        <a href="?tab=security" class="tab-link <?= $activeTab === 'security' ? 'active' : '' ?>">
                            <i class="fas fa-shield-alt"></i> Security
                        </a>
                        <a href="?tab=dashboard" class="tab-link <?= $activeTab === 'dashboard' ? 'active' : '' ?>">
                            <i class="fas fa-tachometer-alt"></i> Dashboard Modules
                        </a>
                    </div>
                    
                    <div class="tab-content">
                        <!-- Site Settings Tab -->
                        <?php if ($activeTab === 'site'): ?>
                            <div class="tab-pane active">
                                <div class="form-container">
                                    <h3><i class="fas fa-globe"></i> Site Settings</h3>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= e(CSRFProtection::generateToken()) ?>">
                                        
                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label for="site_name">Site Name *</label>
                                                <input type="text" 
                                                       id="site_name" 
                                                       name="site_name" 
                                                       class="form-control" 
                                                       value="<?= e($siteSettings['site_name'] ?? '') ?>"
                                                       required 
                                                       maxlength="50">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="company_name">Company Name</label>
                                                <input type="text" 
                                                       id="company_name" 
                                                       name="company_name" 
                                                       class="form-control" 
                                                       value="<?= e($siteSettings['company_name'] ?? '') ?>"
                                                       maxlength="50">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="site_phone">Phone Number</label>
                                                <input type="text" 
                                                       id="site_phone" 
                                                       name="site_phone" 
                                                       class="form-control" 
                                                       value="<?= e($siteSettings['site_phone'] ?? '') ?>"
                                                       maxlength="50">
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="site_description">Site Description</label>
                                            <textarea id="site_description" 
                                                      name="site_description" 
                                                      class="form-control" 
                                                      rows="3"
                                                      maxlength="500"><?= e($siteSettings['site_description'] ?? '') ?></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="site_address">Site Address</label>
                                            <textarea id="site_address" 
                                                      name="site_address" 
                                                      class="form-control" 
                                                      rows="3"
                                                      maxlength="500"><?= e($siteSettings['site_address'] ?? '') ?></textarea>
                                        </div>
                                        
                                        <div class="reset-section">
                                            <h4>Reset Dashboard Metrics</h4>
                                            <p class="text-muted">Reset all dashboard statistics and visitor data.</p>
                                            <button type="button" class="btn btn-danger" onclick="resetMetrics()">
                                                <i class="fas fa-redo"></i> Reset All Metrics
                                            </button>
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Site Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        
                        <!-- User Profile Tab -->
                        <?php elseif ($activeTab === 'user'): ?>
                            <div class="tab-pane active">
                                <div class="form-container">
                                    <h3><i class="fas fa-user"></i> User Profile</h3>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= e(CSRFProtection::generateToken()) ?>">
                                        
                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label for="full_name">Full Name *</label>
                                                <input type="text" 
                                                       id="full_name" 
                                                       name="full_name" 
                                                       class="form-control" 
                                                       value="<?= e($currentUser['full_name'] ?? '') ?>"
                                                       required 
                                                       maxlength="100">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="username">Username</label>
                                                <input type="text" 
                                                       id="username" 
                                                       name="username" 
                                                       class="form-control" 
                                                       value="<?= e($currentUser['username'] ?? '') ?>"
                                                       readonly>
                                                <small class="text-muted">Username cannot be changed</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="email">Email Address *</label>
                                                <input type="email" 
                                                       id="email" 
                                                       name="email" 
                                                       class="form-control" 
                                                       value="<?= e($currentUser['email'] ?? '') ?>"
                                                       required 
                                                       maxlength="100">
                                            </div>
                                        </div>
                                        
                                        <div class="password-section">
                                            <h4>Change Password</h4>
                                            <p class="text-muted">Leave blank to keep current password</p>
                                            
                                            <div class="form-grid">
                                                <div class="form-group">
                                                    <label for="lockout_duration">Lockout Duration (minutes)</label>
                                                    <input type="number" 
                                                           id="lockout_duration" 
                                                           name="lockout_duration" 
                                                           class="form-control" 
                                                           value="<?= e($securitySettings['lockout_duration'] ?? 15) ?>"
                                                           min="1" 
                                                           max="1440">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="session_timeout">Session Timeout (minutes)</label>
                                                    <input type="number" 
                                                           id="session_timeout" 
                                                           name="session_timeout" 
                                                           class="form-control" 
                                                           value="<?= e($securitySettings['session_timeout'] ?? 1440) ?>"
                                                           min="5" 
                                                           max="10080">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="security-section">
                                            <h4>Maintenance Mode</h4>
                                            <p class="text-muted">Put the site under construction to prevent public access.</p>
                                            
                                            <div class="form-group">
                                                <div class="form-check">
                                                    <input type="checkbox" 
                                                           id="under_construction" 
                                                           name="under_construction" 
                                                           class="form-check-input"
                                                           value="yes"
                                                           <?= ($securitySettings['under_construction'] ?? 'no') === 'yes' ? 'checked' : '' ?>>
                                                    <label for="under_construction" class="form-check-label">
                                                        Enable Under Construction Mode
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Security Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        
                        <!-- Dashboard Modules Tab -->
                        <?php elseif ($activeTab === 'dashboard'): ?>
                            <div class="tab-pane active">
                                <div class="form-container">
                                    <h3><i class="fas fa-tachometer-alt"></i> Dashboard Modules</h3>
                                    <p class="text-muted">Configure which modules appear on the dashboard and their order.</p>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= e(CSRFProtection::generateToken()) ?>">
                                        
                                        <div class="dashboard-modules-list" id="dashboardModulesList">
                                            <?php foreach ($dashboardModules as $module): ?>
                                                <div class="dashboard-module-item" data-module-id="<?= e($module['id']) ?>">
                                                    <div class="module-drag-handle">
                                                        <i class="fas fa-grip-vertical"></i>
                                                    </div>
                                                    
                                                    <div class="module-content">
                                                        <div class="form-grid">
                                                            <div class="form-group">
                                                                <label>Module Title</label>
                                                                <input type="text" 
                                                                       name="modules[<?= e($module['id']) ?>][title]" 
                                                                       class="form-control" 
                                                                       value="<?= e($module['module_title']) ?>"
                                                                       maxlength="255"
                                                                       required>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label>Status</label>
                                                                <select name="modules[<?= e($module['id']) ?>][status]" class="form-control form-select">
                                                                    <option value="active" <?= $module['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                                    <option value="inactive" <?= $module['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label>Description</label>
                                                            <input type="text" 
                                                                   name="modules[<?= e($module['id']) ?>][description]" 
                                                                   class="form-control" 
                                                                   value="<?= e($module['module_description']) ?>"
                                                                   maxlength="500">
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label>SQL Query</label>
                                                            <textarea name="modules[<?= e($module['id']) ?>][query]" 
                                                                      class="form-control" 
                                                                      rows="3"
                                                                      required><?= e($module['module_query']) ?></textarea>
                                                            <small class="text-muted">
                                                                Query must return a single row with a 'count' column
                                                            </small>
                                                        </div>
                                                        
                                                        <input type="hidden" 
                                                               name="modules[<?= e($module['id']) ?>][order]" 
                                                               value="<?= e($module['display_order']) ?>" 
                                                               class="module-order">
                                                    </div>
                                                    
                                                    <div class="module-actions">
                                                        <button type="button" 
                                                                class="btn btn-danger btn-sm" 
                                                                onclick="deleteModule(<?= e($module['id']) ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <div class="add-module-section">
                                            <button type="button" class="btn btn-outline" onclick="addDashboardModule()">
                                                <i class="fas fa-plus"></i> Add New Dashboard Module
                                            </button>
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Dashboard Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            if (this.value !== newPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Reset metrics function
        function resetMetrics() {
            if (!confirm('Are you sure you want to reset all dashboard metrics? This will clear all visitor statistics and click data. This action cannot be undone.')) {
                return;
            }
            
            AdminCMS.ajax('ajax/reset_metrics.php', {
                method: 'POST',
                body: JSON.stringify({})
            }).then(response => {
                if (response.success) {
                    AdminCMS.showAlert('All metrics have been reset successfully!', 'success');
                } else {
                    AdminCMS.showAlert('Failed to reset metrics', 'error');
                }
            });
        }
        
        // Send test email function
        function sendTestEmail() {
            const testEmail = document.getElementById('test_email').value;
            if (!testEmail) {
                AdminCMS.showAlert('Please enter a test email address', 'warning');
                return;
            }
            
            AdminCMS.ajax('ajax/send_test_email.php', {
                method: 'POST',
                body: JSON.stringify({
                    test_email: testEmail
                })
            }).then(response => {
                if (response.success) {
                    AdminCMS.showAlert('Test email sent successfully!', 'success');
                } else {
                    AdminCMS.showAlert('Failed to send test email: ' + (response.error || 'Unknown error'), 'error');
                }
            });
        }
        
        // Dashboard modules management
        let moduleIndex = <?= count($dashboardModules) ?>;
        
        function addDashboardModule() {
            const container = document.getElementById('dashboardModulesList');
            const moduleHtml = `
                <div class="dashboard-module-item" data-module-id="new_${moduleIndex}">
                    <div class="module-drag-handle">
                        <i class="fas fa-grip-vertical"></i>
                    </div>
                    
                    <div class="module-content">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Module Title</label>
                                <input type="text" 
                                       name="new_modules[${moduleIndex}][title]" 
                                       class="form-control" 
                                       placeholder="Enter module title"
                                       maxlength="255"
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label>Status</label>
                                <select name="new_modules[${moduleIndex}][status]" class="form-control form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" 
                                   name="new_modules[${moduleIndex}][description]" 
                                   class="form-control" 
                                   placeholder="Enter module description"
                                   maxlength="500">
                        </div>
                        
                        <div class="form-group">
                            <label>SQL Query</label>
                            <textarea name="new_modules[${moduleIndex}][query]" 
                                      class="form-control" 
                                      rows="3"
                                      placeholder="SELECT COUNT(*) as count FROM table_name"
                                      required></textarea>
                            <small class="text-muted">
                                Query must return a single row with a 'count' column
                            </small>
                        </div>
                        
                        <input type="hidden" 
                               name="new_modules[${moduleIndex}][order]" 
                               value="${moduleIndex + 1}" 
                               class="module-order">
                    </div>
                    
                    <div class="module-actions">
                        <button type="button" 
                                class="btn btn-danger btn-sm" 
                                onclick="removeNewModule(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', moduleHtml);
            moduleIndex++;
        }
        
        function removeNewModule(button) {
            button.closest('.dashboard-module-item').remove();
        }
        
        function deleteModule(moduleId) {
            if (!confirm('Are you sure you want to delete this dashboard module?')) {
                return;
            }
            
            AdminCMS.ajax('ajax/delete_dashboard_module.php', {
                method: 'POST',
                body: JSON.stringify({
                    module_id: moduleId
                })
            }).then(response => {
                if (response.success) {
                    location.reload();
                } else {
                    AdminCMS.showAlert('Failed to delete module', 'error');
                }
            });
        }
        
        // Initialize sortable for dashboard modules
        if (document.getElementById('dashboardModulesList')) {
            AdminCMS.initSortables();
        }
    </script>
    
    <style>
        .settings-tabs {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .tab-navigation {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            overflow-x: auto;
        }
        
        .tab-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 15px 20px;
            color: #6c757d;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .tab-link:hover {
            color: #495057;
            background: #e9ecef;
            text-decoration: none;
        }
        
        .tab-link.active {
            color: #007bff;
            background: white;
            border-bottom-color: #007bff;
        }
        
        .tab-content {
            padding: 30px;
        }
        
        .form-container {
            max-width: 800px;
        }
        
        .form-container h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-container h3 i {
            color: #007bff;
        }
        
        .security-section {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .security-section h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
        }
        
        .password-section {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .password-section h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
        }
        
        .reset-section {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #dc3545;
            border-radius: 8px;
            background: #fff5f5;
        }
        
        .reset-section h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #dc3545;
            margin-bottom: 10px;
        }
        
        .test-email-section {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #17a2b8;
            border-radius: 8px;
            background: #f0fdff;
        }
        
        .test-email-section h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #17a2b8;
            margin-bottom: 10px;
        }
        
        .dashboard-modules-list {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
            min-height: 200px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .dashboard-module-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }
        
        .dashboard-module-item:last-child {
            margin-bottom: 0;
        }
        
        .module-drag-handle {
            color: #6c757d;
            cursor: move;
            font-size: 16px;
            margin-top: 5px;
        }
        
        .module-content {
            flex: 1;
        }
        
        .module-actions {
            display: flex;
            gap: 5px;
        }
        
        .add-module-section {
            text-align: center;
            margin: 20px 0;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-check-input {
            margin: 0;
        }
        
        .form-actions {
            padding: 20px 0;
            border-top: 1px solid #e9ecef;
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        @media (max-width: 768px) {
            .tab-content {
                padding: 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-module-item {
                flex-direction: column;
                gap: 15px;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>

<?php
/**
 * Settings Management Functions
 */

function getSiteSettings() {
    $db = DB::getInstance();
    
    try {
        $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_group = 'site'");
        $stmt->execute();
        $settings = $stmt->fetchAll();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $setting['setting_value'];
        }
        
        return $result;
    } catch (Exception $e) {
        ErrorHandler::logError("Failed to get site settings: " . $e->getMessage());
        return [];
    }
}

function updateSiteSettings($data) {
    $db = DB::getInstance();
    
    // Validate inputs
    $siteName = InputValidator::validateText($data['site_name'], 50, true);
    $companyName = InputValidator::validateText($data['company_name'] ?? '', 50, false);
    $sitePhone = InputValidator::validateText($data['site_phone'] ?? '', 50, false);
    $siteDescription = InputValidator::validateText($data['site_description'] ?? '', 500, false);
    $siteAddress = InputValidator::validateText($data['site_address'] ?? '', 500, false);
    
    $settings = [
        'site_name' => $siteName,
        'company_name' => $companyName,
        'site_phone' => $sitePhone,
        'site_description' => $siteDescription,
        'site_address' => $siteAddress
    ];
    
    $stmt = $db->prepare("
        INSERT INTO settings (setting_key, setting_value, setting_group) 
        VALUES (?, ?, 'site') 
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    
    foreach ($settings as $key => $value) {
        $stmt->execute([$key, $value, $value]);
    }
}

function updateUserSettings($data, $userId) {
    $auth = new Auth();
    
    // Validate inputs
    $fullName = InputValidator::validateText($data['full_name'], 100, true);
    $email = InputValidator::validateEmail($data['email'], true);
    
    $newPassword = null;
    if (!empty($data['new_password'])) {
        if (empty($data['current_password'])) {
            throw new Exception("Current password is required to set a new password");
        }
        
        if ($data['new_password'] !== $data['confirm_password']) {
            throw new Exception("New passwords do not match");
        }
        
        // Verify current password
        $db = DB::getInstance();
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($data['current_password'], $user['password'])) {
            throw new Exception("Current password is incorrect");
        }
        
        $newPassword = $data['new_password'];
    }
    
    $auth->updateProfile($userId, $fullName, $email, $newPassword);
}

function getSecuritySettings() {
    $db = DB::getInstance();
    
    try {
        $stmt = $db->prepare("SELECT * FROM security_settings LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch();
        
        if (!$settings) {
            // Create default settings
            $stmt = $db->prepare("INSERT INTO security_settings (id) VALUES (1)");
            $stmt->execute();
            
            $stmt = $db->prepare("SELECT * FROM security_settings LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch();
        }
        
        return $settings;
    } catch (Exception $e) {
        ErrorHandler::logError("Failed to get security settings: " . $e->getMessage());
        return [];
    }
}

function updateSecuritySettings($data) {
    $db = DB::getInstance();
    
    // Validate inputs
    $maxLoginAttempts = InputValidator::validateInteger($data['max_login_attempts'] ?? 5, 1, 20);
    $lockoutDuration = InputValidator::validateInteger($data['lockout_duration'] ?? 15, 1, 1440);
    $sessionTimeout = InputValidator::validateInteger($data['session_timeout'] ?? 1440, 5, 10080);
    $underConstruction = isset($data['under_construction']) ? 'yes' : 'no';
    
    $captchaSiteKey = InputValidator::validateText($data['captcha_site_key'] ?? '', 255, false);
    
    // Handle secret key
    $currentSettings = getSecuritySettings();
    $captchaSecretKey = $currentSettings['captcha_secret_key'] ?? '';
    if (!empty($data['captcha_secret_key'])) {
        $captchaSecretKey = InputValidator::validateText($data['captcha_secret_key'], 255, false);
    }
    
    $stmt = $db->prepare("
        UPDATE security_settings SET 
            captcha_site_key = ?,
            captcha_secret_key = ?,
            under_construction = ?,
            max_login_attempts = ?,
            lockout_duration = ?,
            session_timeout = ?,
            updated_at = NOW()
        WHERE id = 1
    ");
    
    $stmt->execute([
        $captchaSiteKey,
        $captchaSecretKey,
        $underConstruction,
        $maxLoginAttempts,
        $lockoutDuration,
        $sessionTimeout
    ]);
}

function getEmailSettings() {
    $db = DB::getInstance();
    
    try {
        $stmt = $db->prepare("SELECT * FROM email_settings LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch();
        
        if (!$settings) {
            // Create default settings
            $stmt = $db->prepare("INSERT INTO email_settings (id) VALUES (1)");
            $stmt->execute();
            
            $stmt = $db->prepare("SELECT * FROM email_settings LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch();
        }
        
        return $settings;
    } catch (Exception $e) {
        ErrorHandler::logError("Failed to get email settings: " . $e->getMessage());
        return [];
    }
}

function updateEmailSettings($data) {
    $db = DB::getInstance();
    
    // Validate inputs
    $smtpHost = InputValidator::validateText($data['smtp_host'] ?? '', 255, false);
    $smtpPort = InputValidator::validateInteger($data['smtp_port'] ?? 587, 1, 65535);
    $smtpUsername = InputValidator::validateText($data['smtp_username'] ?? '', 255, false);
    $smtpEncryption = in_array($data['smtp_encryption'] ?? 'tls', ['tls', 'ssl', 'none']) ? $data['smtp_encryption'] : 'tls';
    $fromEmail = InputValidator::validateEmail($data['from_email'] ?? '', false);
    $fromName = InputValidator::validateText($data['from_name'] ?? '', 255, false);
    
    // Handle password
    $currentSettings = getEmailSettings();
    $smtpPassword = $currentSettings['smtp_password'] ?? '';
    if (!empty($data['smtp_password'])) {
        $smtpPassword = $data['smtp_password'];
    }
    
    $stmt = $db->prepare("
        UPDATE email_settings SET 
            smtp_host = ?,
            smtp_port = ?,
            smtp_username = ?,
            smtp_password = ?,
            smtp_encryption = ?,
            from_email = ?,
            from_name = ?,
            updated_at = NOW()
        WHERE id = 1
    ");
    
    $stmt->execute([
        $smtpHost,
        $smtpPort,
        $smtpUsername,
        $smtpPassword,
        $smtpEncryption,
        $fromEmail,
        $fromName
    ]);
}

function getDashboardModules() {
    $db = DB::getInstance();
    
    try {
        $stmt = $db->prepare("
            SELECT * FROM dashboard_modules 
            ORDER BY display_order ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        ErrorHandler::logError("Failed to get dashboard modules: " . $e->getMessage());
        return [];
    }
}

function updateDashboardSettings($data) {
    $db = DB::getInstance();
    
    // Update existing modules
    if (isset($data['modules']) && is_array($data['modules'])) {
        $stmt = $db->prepare("
            UPDATE dashboard_modules 
            SET module_title = ?, module_description = ?, module_query = ?, status = ?, display_order = ?
            WHERE id = ?
        ");
        
        foreach ($data['modules'] as $moduleId => $moduleData) {
            $title = InputValidator::validateText($moduleData['title'], 255, true);
            $description = InputValidator::validateText($moduleData['description'] ?? '', 500, false);
            $query = trim($moduleData['query']);
            $status = in_array($moduleData['status'] ?? 'active', ['active', 'inactive']) ? $moduleData['status'] : 'active';
            $order = InputValidator::validateInteger($moduleData['order'] ?? 0, 0);
            
            if (empty($query)) {
                throw new Exception("SQL query is required for dashboard module");
            }
            
            $stmt->execute([$title, $description, $query, $status, $order, $moduleId]);
        }
    }
    
    // Add new modules
    if (isset($data['new_modules']) && is_array($data['new_modules'])) {
        $stmt = $db->prepare("
            INSERT INTO dashboard_modules (module_name, module_title, module_description, module_query, display_order, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($data['new_modules'] as $moduleData) {
            $title = InputValidator::validateText($moduleData['title'], 255, true);
            $description = InputValidator::validateText($moduleData['description'] ?? '', 500, false);
            $query = trim($moduleData['query']);
            $status = in_array($moduleData['status'] ?? 'active', ['active', 'inactive']) ? $moduleData['status'] : 'active';
            $order = InputValidator::validateInteger($moduleData['order'] ?? 0, 0);
            
            if (empty($query)) {
                throw new Exception("SQL query is required for dashboard module");
            }
            
            $moduleName = strtolower(str_replace(' ', '_', $title));
            $stmt->execute([$moduleName, $title, $description, $query, $order, $status]);
        }
    }
}
?>
                                                    <label for="current_password">Current Password</label>
                                                    <input type="password" 
                                                           id="current_password" 
                                                           name="current_password" 
                                                           class="form-control">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="new_password">New Password</label>
                                                    <input type="password" 
                                                           id="new_password" 
                                                           name="new_password" 
                                                           class="form-control"
                                                           minlength="8">
                                                    <small class="text-muted">Minimum 8 characters</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="confirm_password">Confirm New Password</label>
                                                    <input type="password" 
                                                           id="confirm_password" 
                                                           name="confirm_password" 
                                                           class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update Profile
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        
                        <!-- Email Server Tab -->
                        <?php elseif ($activeTab === 'email'): ?>
                            <div class="tab-pane active">
                                <div class="form-container">
                                    <h3><i class="fas fa-envelope"></i> Email Server Settings</h3>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= e(CSRFProtection::generateToken()) ?>">
                                        
                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label for="smtp_host">SMTP Host</label>
                                                <input type="text" 
                                                       id="smtp_host" 
                                                       name="smtp_host" 
                                                       class="form-control" 
                                                       value="<?= e($emailSettings['smtp_host'] ?? '') ?>"
                                                       placeholder="smtp.gmail.com">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="smtp_port">SMTP Port</label>
                                                <input type="number" 
                                                       id="smtp_port" 
                                                       name="smtp_port" 
                                                       class="form-control" 
                                                       value="<?= e($emailSettings['smtp_port'] ?? 587) ?>"
                                                       min="1" 
                                                       max="65535">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="smtp_encryption">Encryption</label>
                                                <select id="smtp_encryption" name="smtp_encryption" class="form-control form-select">
                                                    <option value="tls" <?= ($emailSettings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                                    <option value="ssl" <?= ($emailSettings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                                    <option value="none" <?= ($emailSettings['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="smtp_username">SMTP Username</label>
                                                <input type="text" 
                                                       id="smtp_username" 
                                                       name="smtp_username" 
                                                       class="form-control" 
                                                       value="<?= e($emailSettings['smtp_username'] ?? '') ?>">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="smtp_password">SMTP Password</label>
                                                <input type="password" 
                                                       id="smtp_password" 
                                                       name="smtp_password" 
                                                       class="form-control" 
                                                       placeholder="Leave blank to keep current password">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="from_email">From Email</label>
                                                <input type="email" 
                                                       id="from_email" 
                                                       name="from_email" 
                                                       class="form-control" 
                                                       value="<?= e($emailSettings['from_email'] ?? '') ?>"
                                                       placeholder="noreply@yoursite.com">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="from_name">From Name</label>
                                                <input type="text" 
                                                       id="from_name" 
                                                       name="from_name" 
                                                       class="form-control" 
                                                       value="<?= e($emailSettings['from_name'] ?? '') ?>"
                                                       placeholder="Your Site Name">
                                            </div>
                                        </div>
                                        
                                        <div class="test-email-section">
                                            <h4>Test Email Configuration</h4>
                                            <p class="text-muted">Send a test email to verify your settings work correctly.</p>
                                            <div class="form-group">
                                                <label for="test_email">Test Email Address</label>
                                                <input type="email" 
                                                       id="test_email" 
                                                       name="test_email" 
                                                       class="form-control" 
                                                       placeholder="test@example.com">
                                            </div>
                                            <button type="button" class="btn btn-outline" onclick="sendTestEmail()">
                                                <i class="fas fa-paper-plane"></i> Send Test Email
                                            </button>
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Email Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        
                        <!-- Security Tab -->
                        <?php elseif ($activeTab === 'security'): ?>
                            <div class="tab-pane active">
                                <div class="form-container">
                                    <h3><i class="fas fa-shield-alt"></i> Security Settings</h3>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= e(CSRFProtection::generateToken()) ?>">
                                        
                                        <div class="security-section">
                                            <h4>Google reCAPTCHA</h4>
                                            <p class="text-muted">Configure reCAPTCHA to prevent automated attacks on login forms.</p>
                                            
                                            <div class="form-grid">
                                                <div class="form-group">
                                                    <label for="captcha_site_key">Site Key</label>
                                                    <input type="text" 
                                                           id="captcha_site_key" 
                                                           name="captcha_site_key" 
                                                           class="form-control" 
                                                           value="<?= e($securitySettings['captcha_site_key'] ?? '') ?>"
                                                           placeholder="6Lc...">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="captcha_secret_key">Secret Key</label>
                                                    <input type="password" 
                                                           id="captcha_secret_key" 
                                                           name="captcha_secret_key" 
                                                           class="form-control" 
                                                           placeholder="Leave blank to keep current key">
                                                </div>
                                            </div>
                                            
                                            <small class="text-muted">
                                                Get your reCAPTCHA keys from <a href="https://www.google.com/recaptcha" target="_blank">Google reCAPTCHA</a>
                                            </small>
                                        </div>
                                        
                                        <div class="security-section">
                                            <h4>Login Security</h4>
                                            
                                            <div class="form-grid">
                                                <div class="form-group">
                                                    <label for="max_login_attempts">Max Login Attempts</label>
                                                    <input type="number" 
                                                           id="max_login_attempts" 
                                                           name="max_login_attempts" 
                                                           class="form-control" 
                                                           value="<?= e($securitySettings['max_login_attempts'] ?? 5) ?>"
                                                           min="1" 
                                                           max="20">
                                                </div>
                                                
                                                <div class="form-group">