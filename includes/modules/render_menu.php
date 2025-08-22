<?php
/**
 * Menu Module Renderer
 * Displays navigation menu with mobile hamburger support
 */

// Module data is available in $module variable
$moduleId = $module['id'];
$title = $module['title'];
$backgroundColor = $module['background_color'] ?? '';
$backgroundImage = $module['background_image'] ?? '';

// Get menu items
$db = DB::getInstance();
$stmt = $db->prepare("
    SELECT mi.*, c.title as category_title, c.slug as category_slug 
    FROM menu_items mi 
    JOIN categories c ON mi.category_id = c.id 
    WHERE mi.module_id = ? AND mi.status = 'active' AND c.status = 'active'
    ORDER BY mi.link_order ASC
");
$stmt->execute([$moduleId]);
$menuItems = $stmt->fetchAll();

if (empty($menuItems)) {
    return; // No menu items to display
}

// Build style attributes
$styles = [];
if ($backgroundColor) {
    $styles[] = "background-color: {$backgroundColor}";
}
if ($backgroundImage) {
    $styles[] = "background-image: url('" . UPLOADS_URL . "/images/{$backgroundImage}')";
    $styles[] = "background-size: cover";
    $styles[] = "background-position: center center";
    $styles[] = "background-repeat: no-repeat";
}

$styleAttr = !empty($styles) ? 'style="' . implode('; ', $styles) . '"' : '';

// Get site logo for menu
$themeSettings = getThemeSettings();
$storeLogo = $themeSettings['store_logo'] ?? '';
?>

<div class="module-container menu-module-container" data-module-id="<?= e($moduleId) ?>">
    <nav class="navigation-menu <?= $backgroundImage ? 'module-background' : '' ?>" id="navigation-menu" <?= $styleAttr ?>>
        <?php if ($backgroundImage): ?>
            <div class="module-overlay" style="background: rgba(0,0,0,0.1); position: absolute; top: 0; left: 0; right: 0; bottom: 0; border-radius: 12px;"></div>
        <?php endif; ?>
        
        <div class="menu-container" style="<?= $backgroundImage ? 'position: relative; z-index: 2;' : '' ?>">
            <!-- Logo/Brand -->
            <div class="menu-brand">
                <?php if ($storeLogo): ?>
                    <img src="<?= UPLOADS_URL ?>/logos/<?= e($storeLogo) ?>" 
                         alt="<?= e(getSiteSetting('site_name', 'Logo')) ?>" 
                         class="brand-logo">
                <?php else: ?>
                    <h3 class="brand-text"><?= e(getSiteSetting('site_name', 'CMS Website')) ?></h3>
                <?php endif; ?>
            </div>
            
            <!-- Desktop Menu -->
            <ul class="menu-items">
                <?php foreach ($menuItems as $item): ?>
                    <li class="menu-item">
                        <a href="#<?= e($item['category_slug']) ?>" 
                           class="menu-link"
                           data-category="<?= e($item['category_slug']) ?>">
                            <?= e($item['link_text']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle navigation menu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
    </nav>
    
    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    
    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <div class="mobile-brand">
                <?php if ($storeLogo): ?>
                    <img src="<?= UPLOADS_URL ?>/logos/<?= e($storeLogo) ?>" 
                         alt="<?= e(getSiteSetting('site_name', 'Logo')) ?>" 
                         class="mobile-brand-logo">
                <?php else: ?>
                    <h4 class="mobile-brand-text"><?= e(getSiteSetting('site_name', 'CMS Website')) ?></h4>
                <?php endif; ?>
            </div>
            <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Close navigation menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <ul class="mobile-menu-items">
            <?php foreach ($menuItems as $item): ?>
                <li class="mobile-menu-item">
                    <a href="#<?= e($item['category_slug']) ?>" 
                       class="mobile-menu-link"
                       data-category="<?= e($item['category_slug']) ?>">
                        <?= e($item['link_text']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<style>
.menu-module-container {
    margin: 20px 0;
    position: relative;
    z-index: 1000;
}

.navigation-menu {
    background: rgba(255, 255, 255, 0.95);
    padding: 15px 0;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    margin: 0 20px;
    position: relative;
    transition: all 0.3s ease;
}

.navigation-menu.sticky {
    position: fixed;
    top: 0;
    left: 20px;
    right: 20px;
    margin: 0;
    border-radius: 0 0 12px 12px;
    animation: slideDown 0.3s ease;
    z-index: 1000;
}

@keyframes slideDown {
    from {
        transform: translateY(-100%);
    }
    to {
        transform: translateY(0);
    }
}

.menu-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Brand/Logo */
.menu-brand {
    display: flex;
    align-items: center;
}

.brand-logo {
    max-height: 50px;
    width: auto;
    object-fit: contain;
}

.brand-text {
    color: #333;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    text-decoration: none;
}

/* Desktop Menu */
.menu-items {
    display: flex;
    list-style: none;
    gap: 30px;
    align-items: center;
    margin: 0;
    padding: 0;
}

.menu-item {
    position: relative;
}

.menu-link {
    color: #333;
    text-decoration: none;
    font-weight: 600;
    padding: 10px 15px;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
    display: block;
}

.menu-link:hover {
    background: var(--links-color);
    color: white;
    transform: translateY(-2px);
}

.menu-link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 2px;
    background: var(--links-color);
    transition: all 0.3s ease;
    transform: translateX(-50%);
}

.menu-link:hover::after {
    width: 100%;
}

/* Mobile Menu Toggle */
.mobile-menu-toggle {
    display: none;
    background: var(--links-color);
    border: none;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    flex-direction: column;
    gap: 4px;
    width: 50px;
    height: 50px;
    align-items: center;
    justify-content: center;
}

.mobile-menu-toggle:hover {
    background: #0056b3;
    transform: scale(1.05);
}

.hamburger-line {
    width: 25px;
    height: 3px;
    background: white;
    border-radius: 2px;
    transition: all 0.3s ease;
    transform-origin: center;
}

.mobile-menu-toggle.active .hamburger-line:nth-child(1) {
    transform: rotate(45deg) translate(6px, 6px);
}

.mobile-menu-toggle.active .hamburger-line:nth-child(2) {
    opacity: 0;
}

.mobile-menu-toggle.active .hamburger-line:nth-child(3) {
    transform: rotate(-45deg) translate(6px, -6px);
}

/* Mobile Menu Overlay */
.mobile-menu-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 9998;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.mobile-menu-overlay.active {
    display: block;
    opacity: 1;
}

/* Mobile Menu */
.mobile-menu {
    display: none;
    position: fixed;
    top: 0;
    right: -350px;
    width: 350px;
    height: 100%;
    background: white;
    z-index: 9999;
    transition: right 0.3s ease;
    box-shadow: -5px 0 20px rgba(0, 0, 0, 0.3);
    overflow-y: auto;
}

.mobile-menu.active {
    right: 0;
}

.mobile-menu-header {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
}

.mobile-brand {
    display: flex;
    align-items: center;
}

.mobile-brand-logo {
    max-height: 40px;
    width: auto;
    object-fit: contain;
}

.mobile-brand-text {
    color: #333;
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0;
}

.mobile-menu-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.mobile-menu-close:hover {
    background: #e9ecef;
    color: #333;
}

.mobile-menu-items {
    list-style: none;
    padding: 20px 0;
    margin: 0;
}

.mobile-menu-item {
    margin: 0;
}

.mobile-menu-link {
    display: block;
    padding: 15px 25px;
    color: #333;
    text-decoration: none;
    font-weight: 600;
    border-bottom: 1px solid #f8f9fa;
    transition: all 0.3s ease;
    position: relative;
}

.mobile-menu-link:hover {
    background: var(--links-color);
    color: white;
    padding-left: 35px;
}

.mobile-menu-link::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 0;
    background: var(--links-color);
    transition: height 0.3s ease;
}

.mobile-menu-link:hover::before {
    height: 100%;
}

/* Responsive Breakpoints */
@media (max-width: 768px) {
    .menu-items {
        display: none;
    }
    
    .mobile-menu-toggle {
        display: flex;
    }
    
    .mobile-menu {
        display: block;
    }
    
    .navigation-menu {
        margin: 0 10px;
    }
    
    .navigation-menu.sticky {
        left: 10px;
        right: 10px;
    }
    
    .menu-container {
        padding: 0 15px;
    }
}

@media (max-width: 480px) {
    .mobile-menu {
        width: 100%;
        right: -100%;
    }
    
    .navigation-menu {
        margin: 0 5px;
    }
    
    .navigation-menu.sticky {
        left: 5px;
        right: 5px;
    }
    
    .menu-container {
        padding: 0 10px;
    }
    
    .brand-text {
        font-size: 1.2rem;
    }
    
    .mobile-brand-text {
        font-size: 1rem;
    }
}

/* Accessibility */
.menu-link:focus,
.mobile-menu-link:focus,
.mobile-menu-toggle:focus,
.mobile-menu-close:focus {
    outline: 2px solid var(--links-color);
    outline-offset: 2px;
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .navigation-menu {
        border: 2px solid #000;
    }
    
    .menu-link,
    .mobile-menu-link {
        border: 1px solid transparent;
    }
    
    .menu-link:hover,
    .mobile-menu-link:hover {
        border-color: #000;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .navigation-menu,
    .menu-link,
    .mobile-menu,
    .mobile-menu-overlay,
    .hamburger-line {
        transition: none;
    }
    
    .navigation-menu.sticky {
        animation: none;
    }
}

/* Print styles */
@media print {
    .mobile-menu-toggle,
    .mobile-menu,
    .mobile-menu-overlay {
        display: none !important;
    }
    
    .navigation-menu {
        position: static !important;
        box-shadow: none;
        border: 1px solid #000;
    }
    
    .menu-items {
        display: flex !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileOverlay = document.getElementById('mobileMenuOverlay');
    const menuClose = document.getElementById('mobileMenuClose');
    
    if (menuToggle && mobileMenu && mobileOverlay) {
        // Open mobile menu
        menuToggle.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            mobileOverlay.classList.add('active');
            menuToggle.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        
        // Close mobile menu
        function closeMobileMenu() {
            mobileMenu.classList.remove('active');
            mobileOverlay.classList.remove('active');
            menuToggle.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        if (menuClose) {
            menuClose.addEventListener('click', closeMobileMenu);
        }
        
        mobileOverlay.addEventListener('click', closeMobileMenu);
        
        // Close menu when clicking menu links
        const mobileMenuLinks = document.querySelectorAll('.mobile-menu-link');
        mobileMenuLinks.forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });
        
        // Close menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
                closeMobileMenu();
            }
        });
        
        // Close menu on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && mobileMenu.classList.contains('active')) {
                closeMobileMenu();
            }
        });
    }
    
    // Track menu clicks
    document.querySelectorAll('.menu-link, .mobile-menu-link').forEach(link => {
        link.addEventListener('click', function() {
            const category = this.dataset.category;
            const linkText = this.textContent;
            
            // Send analytics
            if (window.FrontendCMS && window.FrontendCMS.trackEvent) {
                window.FrontendCMS.trackEvent('menu_click', {
                    category: category,
                    link_text: linkText,
                    menu_type: this.classList.contains('mobile-menu-link') ? 'mobile' : 'desktop'
                });
            }
        });
    });
});
</script>