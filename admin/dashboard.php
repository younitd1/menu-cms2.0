<?php
require_once '../config/config.php';

// Require authentication
SecureSession::requireLogin();

// Get current user
$auth = new Auth();
$currentUser = $auth->getCurrentUser();

// Get dashboard modules
function getDashboardModules() {
    try {
        $db = DB::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM dashboard_modules 
            WHERE status = 'active' 
            ORDER BY display_order ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        ErrorHandler::logError("Failed to load dashboard modules: " . $e->getMessage());
        return [];
    }
}

// Execute dashboard module query
function executeDashboardQuery($query) {
    try {
        $db = DB::getInstance();
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        ErrorHandler::logError("Dashboard query failed: " . $e->getMessage());
        return 0;
    }
}

$dashboardModules = getDashboardModules();
$flashMessage = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CMS Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar Navigation -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Top Header -->
            <?php include 'includes/header.php'; ?>
            
            <!-- Content Area -->
            <div class="content-area">
                <div class="page-header">
                    <h1 class="page-title">Dashboard</h1>
                    <div class="page-actions">
                        <a href="settings.php?tab=dashboard" class="btn btn-outline">
                            <i class="fas fa-cog"></i> Configure Dashboard
                        </a>
                    </div>
                </div>
                
                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?= e($flashMessage['type']) ?>">
                        <?= e($flashMessage['message']) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Dashboard Statistics -->
                <div class="dashboard-stats">
                    <?php foreach ($dashboardModules as $module): ?>
                        <?php $value = executeDashboardQuery($module['module_query']); ?>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-<?= getModuleIcon($module['module_name']) ?>"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?= number_format($value) ?></div>
                                <div class="stat-label"><?= e($module['module_title']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="dashboard-section">
                    <h2 class="section-title">Quick Actions</h2>
                    <div class="quick-actions">
                        <a href="products.php?action=add" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="action-content">
                                <h3>Add Product</h3>
                                <p>Create a new product</p>
                            </div>
                        </a>
                        
                        <a href="categories.php?action=add" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-folder-plus"></i>
                            </div>
                            <div class="action-content">
                                <h3>Add Category</h3>
                                <p>Create a new category</p>
                            </div>
                        </a>
                        
                        <a href="modules.php?action=add" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-puzzle-piece"></i>
                            </div>
                            <div class="action-content">
                                <h3>Add Module</h3>
                                <p>Create a new module</p>
                            </div>
                        </a>
                        
                        <a href="../index.php" target="_blank" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-external-link-alt"></i>
                            </div>
                            <div class="action-content">
                                <h3>View Site</h3>
                                <p>Preview front-end</p>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="dashboard-section">
                    <h2 class="section-title">Recent Activity</h2>
                    <div class="activity-grid">
                        <!-- Recent Products -->
                        <div class="activity-card">
                            <h3>Recent Products</h3>
                            <div class="activity-list">
                                <?php 
                                $recentProducts = getRecentProducts();
                                if (empty($recentProducts)): 
                                ?>
                                    <div class="activity-item">
                                        <span class="activity-text">No products yet</span>
                                        <a href="products.php?action=add" class="activity-link">Add one</a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recentProducts as $product): ?>
                                        <div class="activity-item">
                                            <span class="activity-text"><?= e($product['title']) ?></span>
                                            <span class="activity-date"><?= timeAgo($product['created_at']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Recent Categories -->
                        <div class="activity-card">
                            <h3>Recent Categories</h3>
                            <div class="activity-list">
                                <?php 
                                $recentCategories = getRecentCategories();
                                if (empty($recentCategories)): 
                                ?>
                                    <div class="activity-item">
                                        <span class="activity-text">No categories yet</span>
                                        <a href="categories.php?action=add" class="activity-link">Add one</a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recentCategories as $category): ?>
                                        <div class="activity-item">
                                            <span class="activity-text"><?= e($category['title']) ?></span>
                                            <span class="activity-date"><?= timeAgo($category['created_at']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Recent Modules -->
                        <div class="activity-card">
                            <h3>Recent Modules</h3>
                            <div class="activity-list">
                                <?php 
                                $recentModules = getRecentModules();
                                if (empty($recentModules)): 
                                ?>
                                    <div class="activity-item">
                                        <span class="activity-text">No modules yet</span>
                                        <a href="modules.php?action=add" class="activity-link">Add one</a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recentModules as $module): ?>
                                        <div class="activity-item">
                                            <span class="activity-text"><?= e($module['title']) ?></span>
                                            <span class="activity-date"><?= timeAgo($module['created_at']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
</body>
</html>

<?php
/**
 * Helper functions for dashboard
 */

function getModuleIcon($moduleName) {
    $icons = [
        'total_products' => 'box',
        'active_modules' => 'puzzle-piece',
        'total_categories' => 'folder',
        'total_users' => 'users',
        'recent_orders' => 'shopping-cart',
        'total_sales' => 'dollar-sign'
    ];
    
    return $icons[$moduleName] ?? 'chart-bar';
}

function getRecentProducts($limit = 5) {
    try {
        $db = DB::getInstance();
        $stmt = $db->prepare("
            SELECT p.title, p.created_at, c.title as category_title
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function getRecentCategories($limit = 5) {
    try {
        $db = DB::getInstance();
        $stmt = $db->prepare("
            SELECT title, created_at
            FROM categories
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function getRecentModules($limit = 5) {
    try {
        $db = DB::getInstance();
        $stmt = $db->prepare("
            SELECT title, module_type, created_at
            FROM modules
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
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