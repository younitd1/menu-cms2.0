<?php
// Get current user info
$currentUser = $currentUser ?? null;
if (!$currentUser) {
    $auth = new Auth();
    $currentUser = $auth->getCurrentUser();
}
?>

<header class="admin-header">
    <div class="header-content">
        <!-- Left side - Mobile menu and breadcrumb -->
        <div class="header-left">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="breadcrumb">
                <?php 
                $pageTitle = getPageTitle($currentPage);
                echo '<span class="breadcrumb-item">' . e($pageTitle) . '</span>';
                ?>
            </div>
        </div>
        
        <!-- Right side - User menu and actions -->
        <div class="header-right">
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="../index.php" target="_blank" class="quick-action" title="View Site">
                    <i class="fas fa-external-link-alt"></i>
                </a>
                
                <a href="settings.php" class="quick-action" title="Settings">
                    <i class="fas fa-cog"></i>
                </a>
            </div>
            
            <!-- User Menu -->
            <div class="user-menu">
                <div class="user-info" id="userMenuToggle">
                    <div class="user-avatar">
                        <?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?= e($currentUser['full_name'] ?? 'User') ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </div>
                
                <div class="user-dropdown" id="userDropdown">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i>
                        <span>Profile Settings</span>
                    </a>
                    
                    <a href="settings.php" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        <span>System Settings</span>
                    </a>
                    
                    <div class="dropdown-divider"></div>
                    
                    <a href="logout.php" class="dropdown-item logout-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
.admin-header {
    background: white;
    border-bottom: 1px solid #e1e5e9;
    padding: 0 30px;
    height: 70px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 100%;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

.mobile-menu-btn {
    display: none;
    background: none;
    border: none;
    font-size: 20px;
    color: #6c757d;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.mobile-menu-btn:hover {
    background: #f8f9fa;
    color: #495057;
}

.breadcrumb {
    font-size: 24px;
    font-weight: 600;
    color: #2c3e50;
}

.breadcrumb-item {
    color: #2c3e50;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.quick-actions {
    display: flex;
    gap: 10px;
}

.quick-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #f8f9fa;
    color: #6c757d;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.quick-action:hover {
    background: #e9ecef;
    color: #495057;
    transform: translateY(-1px);
}

.user-menu {
    position: relative;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.user-info:hover {
    background: #f8f9fa;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
}

.user-details {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.user-name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
}

.user-role {
    font-size: 12px;
    color: #6c757d;
}

.dropdown-arrow {
    color: #6c757d;
    font-size: 12px;
    transition: transform 0.3s ease;
}

.user-info.active .dropdown-arrow {
    transform: rotate(180deg);
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    min-width: 200px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1000;
}

.user-dropdown.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    color: #495057;
    text-decoration: none;
    transition: background 0.3s ease;
    font-size: 14px;
}

.dropdown-item:hover {
    background: #f8f9fa;
}

.dropdown-item i {
    width: 16px;
    font-size: 14px;
}

.dropdown-divider {
    height: 1px;
    background: #e1e5e9;
    margin: 8px 0;
}

.logout-item:hover {
    background: #fee;
    color: #dc3545;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .admin-header {
        padding: 0 15px;
    }
    
    .mobile-menu-btn {
        display: block;
    }
    
    .breadcrumb {
        font-size: 20px;
    }
    
    .user-details {
        display: none;
    }
    
    .quick-actions {
        display: none;
    }
    
    .user-dropdown {
        right: -10px;
        min-width: 180px;
    }
}

@media (max-width: 480px) {
    .breadcrumb {
        font-size: 18px;
    }
    
    .header-content {
        gap: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userMenuToggle = document.getElementById('userMenuToggle');
    const userDropdown = document.getElementById('userDropdown');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    
    // User menu toggle
    userMenuToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        userDropdown.classList.toggle('active');
        userMenuToggle.classList.toggle('active');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!userMenuToggle.contains(e.target)) {
            userDropdown.classList.remove('active');
            userMenuToggle.classList.remove('active');
        }
    });
    
    // Mobile menu button
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            if (sidebar && overlay) {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('active');
            }
        });
    }
});
</script>

<?php
/**
 * Get page title based on current page
 */
function getPageTitle($currentPage) {
    $titles = [
        'dashboard.php' => 'Dashboard',
        'products.php' => 'Products',
        'categories.php' => 'Categories',
        'modules.php' => 'Modules',
        'theme.php' => 'Theme Settings',
        'settings.php' => 'Settings',
        'profile.php' => 'Profile Settings'
    ];
    
    return $titles[$currentPage] ?? 'Admin Panel';
}
?>