<?php
require_once 'config/config.php';

// Check if site is under construction
$securitySettings = getSecuritySettings();
if (($securitySettings['under_construction'] ?? 'no') === 'yes') {
    include 'under_construction.php';
    exit;
}

// Track visitor statistics
trackVisitor();

// Get theme settings
$themeSettings = getThemeSettings();

// Get all active modules organized by position
$modules = getActiveModulesByPosition();

// Get all active categories with products
$categoriesWithProducts = getCategoriesWithProducts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(getSiteSetting('site_name', 'CMS Website')) ?></title>
    <meta name="description" content="<?= e(getSiteSetting('site_description', 'A modern CMS website')) ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/frontend.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Dynamic Theme Styles -->
    <style>
        :root {
            --main-bg-color: <?= e($themeSettings['full_bg_color'] ?? '#1a269b') ?>;
            --main-font: <?= e($themeSettings['main_font'] ?? 'Arial, sans-serif') ?>;
            --links-color: <?= e($themeSettings['links_color'] ?? '#0066cc') ?>;
            --footer-bg-color: #000000;
            
            <?php for ($i = 1; $i <= 5; $i++): ?>
            --h<?= $i ?>-font: <?= e($themeSettings["h{$i}_font"] ?? 'Arial, sans-serif') ?>;
            --h<?= $i ?>-color: <?= e($themeSettings["h{$i}_color"] ?? ($i <= 2 ? '#ffcc3f' : '#ffffff')) ?>;
            --h<?= $i ?>-size: <?= e($themeSettings["h{$i}_size"] ?? (32 - ($i * 4))) ?>px;
            <?php endfor; ?>
            
            --product-margin: <?= e($themeSettings['product_margin'] ?? 20) ?>px;
            --product-padding: <?= e($themeSettings['product_padding'] ?? 15) ?>px;
            --category-margin: <?= e($themeSettings['category_margin'] ?? 20) ?>px;
            --category-padding: <?= e($themeSettings['category_padding'] ?? 15) ?>px;
        }
        
        body {
            font-family: var(--main-font);
            color: #333;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        
        .full-bg-image,
        .full-bg-color {
            background-color: var(--main-bg-color);
            <?php if (!empty($themeSettings['full_bg_image'])): ?>
            background-image: url('<?= UPLOADS_URL ?>/images/<?= e($themeSettings['full_bg_image']) ?>');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            <?php endif; ?>
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100vh;
        }
        
        <?php for ($i = 1; $i <= 5; $i++): ?>
        h<?= $i ?> {
            font-family: var(--h<?= $i ?>-font);
            color: var(--h<?= $i ?>-color);
            font-size: var(--h<?= $i ?>-size);
            margin: 0 0 15px 0;
        }
        <?php endfor; ?>
        
        a {
            color: var(--links-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        a:hover {
            opacity: 0.8;
        }
        
        .category-section {
            margin: var(--category-margin);
            padding: var(--category-padding);
        }
        
        .product-item {
            margin: var(--product-margin);
            padding: var(--product-padding);
        }
        
        #main-footer {
            background-color: var(--footer-bg-color);
            color: white;
            padding: 40px 20px;
            margin-top: 60px;
        }
        
        /* Go to top button */
        .go-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--links-color);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 18px;
        }
        
        .go-to-top:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .go-to-top.visible {
            display: flex;
        }
    </style>
</head>
<body>
    <div class="full-bg-image full-bg-color">
        
        <!-- Top Modules -->
        <?php renderModulePosition('top-module-1', $modules); ?>
        <?php renderModulePosition('top-module-2', $modules); ?>
        <?php renderModulePosition('top-module-3', $modules); ?>
        <?php renderModulePosition('top-module-4', $modules); ?>
        
        <!-- Products Content -->
        <div id="products-content" class="products-content">
            <?php if (!empty($categoriesWithProducts)): ?>
                <?php foreach ($categoriesWithProducts as $category): ?>
                    <div id="<?= e($category['slug']) ?>" class="category-section">
                        <?php if (!empty($category['background_image'])): ?>
                            <div class="category-background" 
                                 style="background-image: url('<?= UPLOADS_URL ?>/images/<?= e($category['background_image']) ?>');
                                        background-size: cover;
                                        background-repeat: no-repeat;
                                        background-position: center center;
                                        min-height: 250px;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        margin-bottom: 30px;
                                        border-radius: 12px;
                                        position: relative;">
                                <div class="category-overlay" style="background: rgba(0,0,0,0.4); position: absolute; top: 0; left: 0; right: 0; bottom: 0; border-radius: 12px;"></div>
                                <div style="position: relative; z-index: 2; text-align: center; padding: 20px;">
                                    <h1><?= e($category['title']) ?></h1>
                                    <?php if (!empty($category['description'])): ?>
                                        <h4><?= $category['description'] ?></h4>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="category-header" style="text-align: center; margin-bottom: 30px;">
                                <h1><?= e($category['title']) ?></h1>
                                <?php if (!empty($category['description'])): ?>
                                    <h4><?= $category['description'] ?></h4>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Products in this category -->
                        <?php if (!empty($category['products'])): ?>
                            <div class="products-grid">
                                <?php foreach ($category['products'] as $product): ?>
                                    <div class="product-item">
                                        <?php if (!empty($product['image'])): ?>
                                            <div class="product-image <?= e($product['image_alignment']) ?>">
                                                <img src="<?= UPLOADS_URL ?>/images/<?= e($product['image']) ?>" 
                                                     alt="<?= e($product['title']) ?>"
                                                     loading="lazy">
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="product-content">
                                            <h2><?= e($product['title']) ?></h2>
                                            
                                            <?php if (!empty($product['short_description'])): ?>
                                                <div class="product-short-description">
                                                    <h5><?= e($product['short_description']) ?></h5>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($product['full_description'])): ?>
                                                <div class="product-description">
                                                    <h5><?= $product['full_description'] ?></h5>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($product['price'] > 0): ?>
                                                <div class="product-price">
                                                    <strong>$<?= number_format($product['price'], 2) ?></strong>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Order Now Button (tracked) -->
                                            <button class="order-now-btn" onclick="trackOrderNowClick()">
                                                <i class="fas fa-shopping-cart"></i> Order Now
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-products" style="text-align: center; padding: 60px 20px;">
                    <h2>Welcome to Our Store</h2>
                    <h4>Products will appear here once they are added from the admin panel.</h4>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Bottom Modules -->
        <?php renderModulePosition('bottom-module-1', $modules); ?>
        <?php renderModulePosition('bottom-module-2', $modules); ?>
        <?php renderModulePosition('bottom-module-3', $modules); ?>
        <?php renderModulePosition('bottom-module-4', $modules); ?>
        
        <!-- Main Footer -->
        <div id="main-footer" class="main-footer">
            <?php renderModulePosition('footer-module-1', $modules); ?>
            <?php renderModulePosition('footer-module-2', $modules); ?>
            <?php renderModulePosition('footer-module-3', $modules); ?>
            <?php renderModulePosition('footer-module-4', $modules); ?>
            
            <?php if (empty($modules['footer-module-1']) && empty($modules['footer-module-2']) && 
                      empty($modules['footer-module-3']) && empty($modules['footer-module-4'])): ?>
                <div style="text-align: center;">
                    <p>&copy; <?= date('Y') ?> <?= e(getSiteSetting('company_name', 'Your Company')) ?>. All rights reserved.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Go to Top Button -->
        <a href="#" class="go-to-top" id="goToTop">
            <i class="fas fa-arrow-up"></i>
        </a>
        
        <!-- Phone Number Popup (for tracking) -->
        <div id="phonePopup" class="phone-popup" style="display: none;">
            <div class="phone-popup-content">
                <span class="phone-popup-close" onclick="closePhonePopup()">&times;</span>
                <h3>Call Us Now</h3>
                <p>
                    <a href="tel:<?= e(getSiteSetting('site_phone', '')) ?>" onclick="trackPhoneClick()">
                        <i class="fas fa-phone"></i> <?= e(getSiteSetting('site_phone', 'Phone number not set')) ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Main JavaScript -->
    <script src="assets/js/frontend.js"></script>
    
    <script>
        // Initialize frontend functionality
        document.addEventListener('DOMContentLoaded', function() {
            initializeGoToTop();
            initializeStickyMenu();
            initializeSlideshow();
        });
        
        // Track Order Now button clicks
        function trackOrderNowClick() {
            // Send analytics data
            fetch('ajax/track_click.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    click_type: 'order_now'
                })
            });
            
            // Show phone popup or redirect to contact
            showPhonePopup();
        }
        
        // Track phone number clicks
        function trackPhoneClick() {
            fetch('ajax/track_click.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    click_type: 'phone_number'
                })
            });
        }
        
        // Show phone popup
        function showPhonePopup() {
            document.getElementById('phonePopup').style.display = 'flex';
        }
        
        // Close phone popup
        function closePhonePopup() {
            document.getElementById('phonePopup').style.display = 'none';
        }
        
        // Go to top functionality
        function initializeGoToTop() {
            const goToTopBtn = document.getElementById('goToTop');
            
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    goToTopBtn.classList.add('visible');
                } else {
                    goToTopBtn.classList.remove('visible');
                }
            });
            
            goToTopBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
        
        // Sticky menu functionality
        function initializeStickyMenu() {
            const menuModule = document.querySelector('.navigation-menu');
            if (menuModule) {
                let menuTop = menuModule.offsetTop;
                
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > menuTop) {
                        menuModule.classList.add('sticky');
                    } else {
                        menuModule.classList.remove('sticky');
                    }
                });
            }
        }
        
        // Slideshow functionality
        function initializeSlideshow() {
            const slideshows = document.querySelectorAll('.slideshow-container');
            
            slideshows.forEach(function(slideshow) {
                const slides = slideshow.querySelectorAll('.slide');
                const dots = slideshow.querySelectorAll('.slideshow-dot');
                let currentSlide = 0;
                const autoplaySpeed = parseInt(slideshow.dataset.autoplaySpeed || '5') * 1000;
                
                function showSlide(index) {
                    slides.forEach((slide, i) => {
                        slide.classList.toggle('active', i === index);
                    });
                    
                    dots.forEach((dot, i) => {
                        dot.classList.toggle('active', i === index);
                    });
                }
                
                function nextSlide() {
                    currentSlide = (currentSlide + 1) % slides.length;
                    showSlide(currentSlide);
                }
                
                // Auto advance slides
                setInterval(nextSlide, autoplaySpeed);
                
                // Dot navigation
                dots.forEach((dot, index) => {
                    dot.addEventListener('click', () => {
                        currentSlide = index;
                        showSlide(currentSlide);
                    });
                });
                
                // Initialize first slide
                showSlide(0);
            });
        }
    </script>
</body>
</html>

<?php
/**
 * Frontend Helper Functions
 */

function getSecuritySettings() {
    $db = DB::getInstance();
    try {
        $stmt = $db->prepare("SELECT * FROM security_settings LIMIT 1");
        $stmt->execute();
        return $stmt->fetch() ?: [];
    } catch (Exception $e) {
        return [];
    }
}

function getThemeSettings() {
    $db = DB::getInstance();
    try {
        $stmt = $db->prepare("SELECT * FROM theme_settings LIMIT 1");
        $stmt->execute();
        return $stmt->fetch() ?: [];
    } catch (Exception $e) {
        return [];
    }
}

function getSiteSetting($key, $default = '') {
    static $settings = null;
    
    if ($settings === null) {
        $db = DB::getInstance();
        try {
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_group = 'site'");
            $stmt->execute();
            $rows = $stmt->fetchAll();
            
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            $settings = [];
        }
    }
    
    return $settings[$key] ?? $default;
}

function getActiveModulesByPosition() {
    $db = DB::getInstance();
    try {
        $stmt = $db->prepare("
            SELECT * FROM modules 
            WHERE status = 'active' 
            ORDER BY position, display_order ASC
        ");
        $stmt->execute();
        $modules = $stmt->fetchAll();
        
        $grouped = [];
        foreach ($modules as $module) {
            $grouped[$module['position']][] = $module;
        }
        
        return $grouped;
    } catch (Exception $e) {
        return [];
    }
}

function getCategoriesWithProducts() {
    $db = DB::getInstance();
    try {
        // Get active categories
        $stmt = $db->prepare("
            SELECT * FROM categories 
            WHERE status = 'active' 
            ORDER BY created_at ASC
        ");
        $stmt->execute();
        $categories = $stmt->fetchAll();
        
        // Get products for each category
        foreach ($categories as &$category) {
            $stmt = $db->prepare("
                SELECT * FROM products 
                WHERE category_id = ? AND status = 'active' 
                ORDER BY created_at ASC
            ");
            $stmt->execute([$category['id']]);
            $category['products'] = $stmt->fetchAll();
        }
        
        return $categories;
    } catch (Exception $e) {
        return [];
    }
}

function renderModulePosition($position, $modules) {
    if (!isset($modules[$position])) {
        return;
    }
    
    foreach ($modules[$position] as $module) {
        include 'includes/modules/render_' . $module['module_type'] . '.php';
    }
}

function trackVisitor() {
    try {
        $db = DB::getInstance();
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $pageVisited = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Simple geolocation (you could integrate with a service like MaxMind)
        $country = 'Unknown';
        $state = 'Unknown';
        
        $stmt = $db->prepare("
            INSERT INTO visitor_stats (ip_address, user_agent, page_visited, country, state, visit_date) 
            VALUES (?, ?, ?, ?, ?, CURDATE())
        ");
        $stmt->execute([$ipAddress, $userAgent, $pageVisited, $country, $state]);
    } catch (Exception $e) {
        // Silently fail - don't break the site for tracking issues
        error_log("Visitor tracking failed: " . $e->getMessage());
    }
}
?>