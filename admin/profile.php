<?php
require_once '../config/config.php';
SecureSession::requireLogin();

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

$message = '';
$messageType = 'info';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!CSRFProtection::validateToken($_POST['csrf_token'] ?? '')) {
            throw new Exception("Invalid request. Please try again.");
        }
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            // Update profile information
            $fullName = $_POST['full_name'] ?? '';
            $email = $_POST['email'] ?? '';
            
            $auth->updateProfile($currentUser['id'], $fullName, $email);
            
            $message = 'Profile updated successfully!';
            $messageType = 'success';
            
            // Refresh user data
            $currentUser = $auth->getCurrentUser();
            
        } elseif ($action === 'change_password') {
            // Change password
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception("All password fields are required");
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception("New passwords do not match");
            }
            
            $auth->updateProfile($currentUser['id'], $currentUser['full_name'], $currentUser['email'], $newPassword);
            
            $message = 'Password changed successfully!';
            $messageType = 'success';
            
        } elseif ($action === 'clear_sessions') {
            // Clear all user sessions (logout from all devices)
            clearAllUserSessions($currentUser['id']);
            
            $message = 'All sessions cleared successfully! You will need to login again on other devices.';
            $messageType = 'success';
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get user activity logs
$userActivity = getUserActivity($currentUser['id']);

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
    <title>Profile Settings - CMS Admin</title>
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
                    <h1 class="page-title">Profile Settings</h1>
                    <div class="page-actions">
                        <a href="dashboard.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
                
                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?= e($flashMessage['type']) ?>">
                        <?= e($flashMessage['message']) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Profile Tabs -->
                <div class="profile-tabs">
                    <div class="tab-navigation">
                        <a href="#profile" class="tab-link active" data-tab="profile">
                            <i class="fas fa-user"></i> Profile Information
                        </a>
                        <a href="#password" class="tab-link" data-tab="password">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                        <a href="#security" class="tab-link" data-tab="security">
                            <i class="fas fa-shield-alt"></i> Security
                        </a>
                        <a href="#activity" class="tab-link" data-tab="activity">
                            <i class="fas fa-history"></i> Activity Log
                        </a>
                    </div>
                    
                    <div class="tab-content">
                        <!-- Profile Information Tab -->
                        <div class="tab-pane active" id="profile-tab">
                            <div class="form-container">
                                <h3><i class="fas fa-user"></i> Profile Information</h3>
                                <p class="text-muted">Update your personal information and contact details.</p>
                                
                                <form method="POST" id="profileForm">
                                    <input type="hidden" name="csrf_token" value="<?= e(CSRFProtection::generateToken()) ?>">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="user-avatar-section">
                                        <div class="current-avatar">
                                            <div class="avatar-circle">
                                                <?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?>
                                            </div>
                                        </div>
                                        <div class="avatar-info">
                                            <h4><?= e($currentUser['full_name'] ?? 'User') ?></h4>
                                            <p class="text-muted">Administrator</p>
                                            <small class="text-muted">Member since <?= date('F Y', strtotime($currentUser['created_at'])) ?></small>
                                        </div>
                                    </div>
                                    
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
                                                   readonly
                                                   title="Username cannot be changed">
                                            <small class="text-muted">Username cannot be changed for security reasons</small>
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
                                        
                                        <div class="form-group">
                                            <label>Account Created</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   value="<?= date('F j, Y g:i A', strtotime($currentUser['created_at'])) ?>"
                                                   readonly>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Last Login</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   value="<?= $currentUser['last_login'] ? date('F j, Y g:i A', strtotime($currentUser['last_login'])) : 'Never' ?>"
                                                   readonly>
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
                        
                        <!-- Change Password Tab -->
                        <div class="tab-pane" id="password-tab">
                            <div class="form-container">
                                <h3><i class="fas fa-key"></i> Change Password</h3>
                                <p class="text-muted">Update your password to keep your account secure.</p>
                                
                                <div class="security-tips">
                                    <h4><i class="fas fa-lightbulb"></i> Password Security Tips:</h4>
                                    <ul>
                                        <li>Use at least 8 characters with a mix of letters, numbers, and symbols</li>
                                        <li>Avoid using personal information or common words</li>
                                        <li>Don't reuse passwords from other accounts</li>
                                        <li>Consider using a password manager</li>
                                    </ul>
                                </div>
                                
                                <form method="POST" id="passwordForm">
                                    <input type="hidden" name="csrf_token" value="<?= e(CSRFProtection::generateToken()) ?>">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="form-group">
                                        <label for="current_password">Current Password *</label>
                                        <input type="password" 
                                               id="current_password" 
                                               name="current_password" 
                                               class="form-control" 
                                               required
                                               autocomplete="current-password">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new_password">New Password *</label>
                                        <input type="password" 
                                               id="new_password" 
                                               name="new_password" 
                                               class="form-control" 
                                               required
                                               minlength="8"
                                               autocomplete="new-password">
                                        <div class="password-strength" id="passwordStrength"></div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password *</label>
                                        <input type="password" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               class="form-control" 
                                               required
                                               autocomplete="new-password">
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-key"></i> Change Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Security Tab -->
                        <div class="tab-pane" id="security-tab">
                            <div class="form-container">
                                <h3><i class="fas fa-shield-alt"></i> Security Settings</h3>
                                <p class="text-muted">Manage your account security and active sessions.</p>
                                
                                <div class="security-section">
                                    <h4><i class="fas fa-desktop"></i> Active Sessions</h4>
                                    <p class="text-muted">Manage devices and sessions that have access to your account.</p>
                                    
                                    <div class="session-item current-session">
                                        <div class="session-info">
                                            <div class="session-device">
                                                <i class="fas fa-desktop"></i>
                                                <strong>Current Session</strong>
                                            </div>
                                            <div class="session-details">
                                                <small class="text-muted">
                                                    <?= e($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown browser') ?><br>
                                                    IP: <?= e($_SERVER['REMOTE_ADDR'] ?? 'Unknown') ?><br>
                                                    Active now
                                                </small>
                                            </div>
                                        </div>
                                        <div class="session-status">
                                            <span class="badge badge-success">Active</span>
                                        </div>
                                    </div>
                                    
                                    <form method="POST" class="mt-3">
                                        <input type="hidden" name="csrf_token" value="<?= e(CSRFProtection::generateToken()) ?>">
                                        <input type="hidden" name="action" value="clear_sessions">
                                        
                                        <button type="submit" class="btn btn-danger" data-confirm="This will log you out from all other devices. Continue?">
                                            <i class="fas fa-sign-out-alt"></i> Clear All Sessions
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="security-section">
                                    <h4><i class="fas fa-clock"></i> Session Information</h4>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <label>Session Started:</label>
                                            <span><?= date('F j, Y g:i A', $_SESSION['login_time'] ?? time()) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <label>Session Expires:</label>
                                            <span><?= date('F j, Y g:i A', ($_SESSION['login_time'] ?? time()) + SESSION_LIFETIME) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <label>User Agent:</label>
                                            <span class="text-break"><?= e(substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 100)) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <label>IP Address:</label>
                                            <span><?= e($_SERVER['REMOTE_ADDR'] ?? 'Unknown') ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Activity Log Tab -->
                        <div class="tab-pane" id="activity-tab">
                            <div class="form-container">
                                <h3><i class="fas fa-history"></i> Activity Log</h3>
                                <p class="text-muted">Recent activity and login history for your account.</p>
                                
                                <div class="activity-list">
                                    <?php if (!empty($userActivity)): ?>
                                        <?php foreach ($userActivity as $activity): ?>
                                            <div class="activity-item">
                                                <div class="activity-icon">
                                                    <?php if ($activity['activity_type'] === 'login'): ?>
                                                        <i class="fas fa-sign-in-alt text-success"></i>
                                                    <?php elseif ($activity['activity_type'] === 'logout'): ?>
                                                        <i class="fas fa-sign-out-alt text-info"></i>
                                                    <?php elseif ($activity['activity_type'] === 'password_change'): ?>
                                                        <i class="fas fa-key text-warning"></i>
                                                    <?php elseif ($activity['activity_type'] === 'profile_update'): ?>
                                                        <i class="fas fa-user-edit text-primary"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-info-circle text-muted"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="activity-content">
                                                    <div class="activity-description">
                                                        <?= e($activity['description']) ?>
                                                    </div>
                                                    <div class="activity-meta">
                                                        <small class="text-muted">
                                                            <?= timeAgo($activity['created_at']) ?> 
                                                            <?php if (!empty($activity['ip_address'])): ?>
                                                                • IP: <?= e($activity['ip_address']) ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="activity-time">
                                                    <small class="text-muted">
                                                        <?= date('M j, g:i A', strtotime($activity['created_at'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="no-activity">
                                            <i class="fas fa-history"></i>
                                            <p>No recent activity to display.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabLinks = document.querySelectorAll('.tab-link');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            // Handle hash-based navigation
            function showTab(tabId) {
                // Remove active class from all tabs and panes
                tabLinks.forEach(link => link.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));
                
                // Add active class to current tab and pane
                const activeLink = document.querySelector(`[data-tab="${tabId}"]`);
                const activePane = document.getElementById(`${tabId}-tab`);
                
                if (activeLink && activePane) {
                    activeLink.classList.add('active');
                    activePane.classList.add('active');
                }
            }
            
            // Tab click handlers
            tabLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabId = this.dataset.tab;
                    showTab(tabId);
                    window.location.hash = tabId;
                });
            });
            
            // Handle initial hash
            const hash = window.location.hash.substr(1);
            if (hash && ['profile', 'password', 'security', 'activity'].includes(hash)) {
                showTab(hash);
            }
        });
        
        // Password confirmation validation
        const confirmPassword = document.getElementById('confirm_password');
        const newPassword = document.getElementById('new_password');
        
        if (confirmPassword && newPassword) {
            confirmPassword.addEventListener('input', function() {
                if (this.value !== newPassword.value) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Password strength indicator
            newPassword.addEventListener('input', function() {
                const password = this.value;
                const strengthDiv = document.getElementById('passwordStrength');
                
                let score = 0;
                let feedback = [];
                
                if (password.length >= 8) score++;
                else feedback.push('At least 8 characters');
                
                if (/[A-Z]/.test(password)) score++;
                else feedback.push('One uppercase letter');
                
                if (/[a-z]/.test(password)) score++;
                else feedback.push('One lowercase letter');
                
                if (/\d/.test(password)) score++;
                else feedback.push('One number');
                
                if (/[^A-Za-z0-9]/.test(password)) score++;
                else feedback.push('One special character');
                
                const strengthLabels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
                const strengthColors = ['#f44336', '#ff9800', '#ffc107', '#4caf50', '#2196f3'];
                
                if (password.length > 0) {
                    const strengthText = strengthLabels[Math.min(score, 4)] || 'Very Weak';
                    const strengthColor = strengthColors[Math.min(score, 4)] || '#f44336';
                    
                    strengthDiv.innerHTML = `
                        <div class="strength-bar">
                            <div class="strength-fill" style="width: ${(score + 1) * 20}%; background-color: ${strengthColor};"></div>
                        </div>
                        <div class="strength-text" style="color: ${strengthColor};">
                            Password Strength: ${strengthText}
                            ${feedback.length > 0 ? ` (Missing: ${feedback.join(', ')})` : ''}
                        </div>
                    `;
                } else {
                    strengthDiv.innerHTML = '';
                }
            });
        }
        
        // Email validation
        document.getElementById('email')?.addEventListener('input', function() {
            if (this.value.length > 100) {
                this.setCustomValidity('Email must be 100 characters or less');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Form submission validation
        document.getElementById('profileForm')?.addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            
            if (!fullName || !email) {
                e.preventDefault();
                AdminCMS.showAlert('Please fill in all required fields', 'error');
                return false;
            }
        });
        
        document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (!currentPassword || !newPassword || !confirmPassword) {
                e.preventDefault();
                AdminCMS.showAlert('Please fill in all password fields', 'error');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                AdminCMS.showAlert('New passwords do not match', 'error');
                return false;
            }
        });
        
        // Auto-refresh activity log every 5 minutes
        setInterval(function() {
            if (document.getElementById('activity-tab').classList.contains('active')) {
                location.reload();
            }
        }, 300000);
    </script>
    
    <style>
        .profile-tabs {
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
            min-width: 150px;
            justify-content: center;
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
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        .form-container {
            max-width: 800px;
        }
        
        .form-container h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-container h3 i {
            color: #007bff;
        }
        
        .user-avatar-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        
        .avatar-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .avatar-info h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        
        .avatar-info p {
            margin: 0 0 5px 0;
            color: #007bff;
            font-weight: 600;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .security-tips {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #2196f3;
        }
        
        .security-tips h4 {
            color: #1976d2;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .security-tips ul {
            margin: 0;
            padding-left: 20px;
            color: #1976d2;
        }
        
        .security-tips li {
            margin-bottom: 8px;
        }
        
        .session-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 10px;
            border: 1px solid #dee2e6;
        }
        
        .current-session {
            border-color: #28a745;
            background: #f8fff8;
        }
        
        .session-device {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .session-device i {
            color: #007bff;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .info-item label {
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        
        .info-item span {
            color: #6c757d;
            font-size: 14px;
        }
        
        .activity-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-description {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .activity-time {
            flex-shrink: 0;
            text-align: right;
        }
        
        .no-activity {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .no-activity i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .password-strength {
            margin-top: 10px;
        }
        
        .strength-bar {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .strength-text {
            font-size: 12px;
            font-weight: 600;
        }
        
        .text-break {
            word-break: break-all;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .tab-navigation {
                flex-direction: column;
            }
            
            .tab-link {
                min-width: auto;
                justify-content: flex-start;
            }
            
            .tab-content {
                padding: 15px;
            }
            
            .user-avatar-section {
                flex-direction: column;
                text-align: center;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .session-item {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .activity-item {
                flex-direction: column;
                gap: 10px;
            }
            
            .activity-time {
                text-align: left;
            }
        }
    </style>
</body>
</html>

<?php
/**
 * Profile Helper Functions
 */

function clearAllUserSessions($userId) {
    // In a more advanced system, you would clear all sessions for this user
    // For now, we just clear the current session
    SecureSession::destroy();
    SecureSession::start();
    
    // Log the action
    ErrorHandler::logError("All sessions cleared for user", [
        'user_id' => $userId,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
}

function getUserActivity($userId, $limit = 20) {
    // This is a simplified version - in a real system you'd have an activity log table
    // For now, we'll return some sample data based on available information
    $activities = [];
    
    try {
        $db = DB::getInstance();
        
        // Get user data for last login
        $stmt = $db->prepare("SELECT last_login FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && $user['last_login']) {
            $activities[] = [
                'activity_type' => 'login',
                'description' => 'Logged into admin panel',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'created_at' => $user['last_login']
            ];
        }
        
        // Get recent login attempts (both successful and failed)
        $stmt = $db->prepare("
            SELECT * FROM login_attempts 
            WHERE username = (SELECT username FROM users WHERE id = ?) 
            ORDER BY attempt_time DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit - count($activities)]);
        $attempts = $stmt->fetchAll();
        
        foreach ($attempts as $attempt) {
            $activities[] = [
                'activity_type' => 'login_attempt',
                'description' => 'Login attempt',
                'ip_address' => $attempt['ip_address'],
                'created_at' => $attempt['attempt_time']
            ];
        }
        
        // Sort by date
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($activities, 0, $limit);
        
    } catch (Exception $e) {
        ErrorHandler::logError("Failed to get user activity: " . $e->getMessage());
        return [];
    }
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hrs ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}
?>