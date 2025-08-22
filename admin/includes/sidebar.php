<?php
// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// Get backend logo
function getBackendLogo() {
    try {
        $db = DB::getInstance();
        $stmt = $db->prepare("SELECT backend_logo FROM theme_settings LIMIT 1");
        $stmt->execute();
        $theme = $stmt->fetch();
        
        if ($theme && !empty($theme['backend_logo']) && file_exists('../uploads/logos/' . $theme['backend_logo'])) {
            return UPLOADS_URL . '/logos/' . e($theme['backend_logo']);
        }
    } catch (Exception $e) {
        // Fallback if error
    }
    return null;
}

$backendLogo = getBackendLogo();
?>

<div class="sidebar">
    <!-- Logo Area -->
    <div class="sidebar-logo">
        <?php if ($backendLogo): ?>
            <img src="<?= $backendLogo ?>" alt="Admin Logo" class="logo-img">
        <?php else: ?>
            <h2 class="logo-text">CMS Admin</h2>
        <?php endif; ?>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="products.php" class="nav-link <?= $currentPage === 'products.php' ? 'active' : '' ?>">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="categories.php" class="nav-link <?= $currentPage === 'categories.php' ? 'active' : '' ?>">
                    <i class="fas fa-folder"></i>
                    <span>Categories</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="modules.php" class="nav-link <?= $currentPage === 'modules.php' ? 'active' : '' ?>">
                    <i class="fas fa-puzzle-piece"></i>
                    <span>Modules</span>
                </a>
            </li>
            
            <!-- Divider -->
            <li class="nav-divider"></li>
            
            <li class="nav-item">
                <a href="theme.php" class="nav-link <?= $currentPage === 'theme.php' ? 'active' : '' ?>">
                    <i class="fas fa-palette"></i>
                    <span>Theme</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="logout.php" class="nav-link logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Mobile Menu Toggle -->
    <div class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </div>
</div>

<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<style>
.sidebar {
    width: 260px;
    background: #2c3e50;
    color: white;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    overflow-y: auto;
    transition: transform 0.3s ease;
    z-index: 1000;
}

.sidebar-logo {
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid #34495e;
}

.logo-img {
    max-width: 100%;
    max-height: 60px;
    object-fit: contain;
}

.logo-text {
    color: #ecf0f1;
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.sidebar-nav {
    padding: 20px 0;
}

.nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin: 5px 0;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #bdc3c7;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px;
}

.nav-link:hover {
    background: #34495e;
    color: white;
    transform: translateX(5px);
}

.nav-link.active {
    background: #3498db;
    color: white;
    box-shadow: inset 3px 0 0 #2980b9;
}

.nav-link i {
    width: 20px;
    margin-right: 12px;
    font-size: 16px;
}

.nav-divider {
    height: 1px;
    background: #34495e;
    margin: 15px 20px;
}

.logout-link:hover {
    background: #e74c3c !important;
    color: white !important;
}

.mobile-menu-toggle {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    background: #3498db;
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 1001;
    transition: all 0.3s ease;
}

.mobile-menu-toggle:hover {
    background: #2980b9;
    transform: scale(1.1);
}

.mobile-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.mobile-open {
        transform: translateX(0);
    }
    
    .mobile-menu-toggle {
        display: flex;
    }
    
    .mobile-overlay.active {
        display: block;
    }
    
    .main-content {
        margin-left: 0 !important;
    }
}

/* Scrollbar Styling */
.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: #2c3e50;
}

.sidebar::-webkit-scrollbar-thumb {
    background: #34495e;
    border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: #4a6374;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('mobileOverlay');
    
    // Mobile menu toggle
    mobileToggle.addEventListener('click', function() {
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('active');
    });
    
    // Close menu when clicking overlay
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
    });
    
    // Close menu when clicking nav link on mobile
    document.querySelectorAll('.nav-link').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
            }
        });
    });
});
</script>