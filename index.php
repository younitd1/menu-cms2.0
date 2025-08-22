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

/**
 * Get site favicon with fallback
 */
function getSiteFavicon($conn) {
    $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_name = 'favicon'");
    $stmt->execute();
    $result = $stmt->get_result();
    $favicon = $result->fetch_assoc();
    
    if ($favicon && !empty($favicon['setting_value'])) {
        return 'uploads/logos/' . $favicon['setting_value'];
    }
    
    return 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y=".9em" font-size="90">🌐</text></svg>';
}

/**
 * Get structured data for SEO
 */
function getStructuredData($conn) {
    // Get site settings
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM site_settings WHERE setting_name IN ('site_name', 'site_description', 'contact_email', 'contact_phone', 'site_logo')");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_name']] = $row['setting_value'];
    }
    
    $structured_data = [
        "@context" => "https://schema.org",
        "@type" => "Organization",
        "name" => $settings['site_name'] ?? 'CMS Website',
        "description" => $settings['site_description'] ?? '',
        "url" => getCurrentUrl(),
    ];
    
    if (!empty($settings['site_logo'])) {
        $structured_data['logo'] = getCurrentUrl() . '/uploads/logos/' . $settings['site_logo'];
    }
    
    if (!empty($settings['contact_email'])) {
        $structured_data['email'] = $settings['contact_email'];
    }
    
    if (!empty($settings['contact_phone'])) {
        $structured_data['telephone'] = $settings['contact_phone'];
    }
    
    return json_encode($structured_data, JSON_UNESCAPED_SLASHES);
}

/**
 * Get current URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host;
}

/**
 * Generate breadcrumb navigation
 */
function generateBreadcrumbs($current_page = '') {
    $breadcrumbs = '<nav aria-label="breadcrumb" class="breadcrumb-nav">';
    $breadcrumbs .= '<ol class="breadcrumb">';
    $breadcrumbs .= '<li class="breadcrumb-item"><a href="/">Home</a></li>';
    
    if (!empty($current_page)) {
        $breadcrumbs .= '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($current_page) . '</li>';
    }
    
    $breadcrumbs .= '</ol>';
    $breadcrumbs .= '</nav>';
    
    return $breadcrumbs;
}

/**
 * Get social media meta tags
 */
function getSocialMetaTags($conn, $title = '', $description = '', $image = '') {
    // Get site settings
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM site_settings WHERE setting_name IN ('site_name', 'site_description', 'site_logo')");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_name']] = $row['setting_value'];
    }
    
    $site_name = $settings['site_name'] ?? 'CMS Website';
    $site_description = $settings['site_description'] ?? '';
    $site_logo = $settings['site_logo'] ?? '';
    
    $meta_title = !empty($title) ? $title : $site_name;
    $meta_description = !empty($description) ? $description : $site_description;
    $meta_image = !empty($image) ? $image : (!empty($site_logo) ? getCurrentUrl() . '/uploads/logos/' . $site_logo : '');
    $current_url = getCurrentUrl() . $_SERVER['REQUEST_URI'];
    
    $meta_tags = '';
    
    // Open Graph meta tags
    $meta_tags .= '<meta property="og:title" content="' . htmlspecialchars($meta_title) . '">' . "\n";
    $meta_tags .= '<meta property="og:description" content="' . htmlspecialchars($meta_description) . '">' . "\n";
    $meta_tags .= '<meta property="og:url" content="' . htmlspecialchars($current_url) . '">' . "\n";
    $meta_tags .= '<meta property="og:site_name" content="' . htmlspecialchars($site_name) . '">' . "\n";
    $meta_tags .= '<meta property="og:type" content="website">' . "\n";
    
    if (!empty($meta_image)) {
        $meta_tags .= '<meta property="og:image" content="' . htmlspecialchars($meta_image) . '">' . "\n";
        $meta_tags .= '<meta property="og:image:alt" content="' . htmlspecialchars($meta_title) . '">' . "\n";
    }
    
    // Twitter Card meta tags
    $meta_tags .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
    $meta_tags .= '<meta name="twitter:title" content="' . htmlspecialchars($meta_title) . '">' . "\n";
    $meta_tags .= '<meta name="twitter:description" content="' . htmlspecialchars($meta_description) . '">' . "\n";
    
    if (!empty($meta_image)) {
        $meta_tags .= '<meta name="twitter:image" content="' . htmlspecialchars($meta_image) . '">' . "\n";
    }
    
    return $meta_tags;
}

/**
 * Track page view for analytics
 */
function trackPageView($conn) {
    try {
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $page_url = $_SERVER['REQUEST_URI'] ?? '/';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        
        // Get country from IP (simplified)
        $country = getCountryFromIP($user_ip);
        
        // Insert visitor stats
        $stmt = $conn->prepare("INSERT INTO visitor_stats (ip_address, user_agent, page_url, referrer, country, visit_time) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $user_ip, $user_agent, $page_url, $referrer, $country);
        $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Page view tracking error: " . $e->getMessage());
    }
}

/**
 * Get country from IP address (simplified version)
 */
function getCountryFromIP($ip) {
    // In production, you would use a proper GeoIP service
    // This is a simplified version
    if ($ip === '127.0.0.1' || $ip === 'localhost' || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false) {
        return 'Local';
    }
    
    // You can integrate with services like:
    // - MaxMind GeoIP2
    // - ip-api.com
    // - ipinfo.io
    
    return 'Unknown';
}

/**
 * Get performance metrics
 */
function trackPerformanceMetrics($conn, $page_load_time) {
    try {
        $page_url = $_SERVER['REQUEST_URI'] ?? '/';
        $memory_usage = memory_get_peak_usage(true);
        $server_response_time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        
        $stmt = $conn->prepare("INSERT INTO performance_metrics (page_url, load_time, memory_usage, server_response_time, recorded_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("sddd", $page_url, $page_load_time, $memory_usage, $server_response_time);
        $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Performance tracking error: " . $e->getMessage());
    }
}

/**
 * Generate CSS variables from theme settings
 */
function generateThemeCSS($conn) {
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM site_settings WHERE setting_name LIKE '%color%' OR setting_name LIKE '%font%' OR setting_name LIKE '%size%' OR setting_name LIKE '%padding%' OR setting_name LIKE '%margin%'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $css = ":root {\n";
    
    while ($row = $result->fetch_assoc()) {
        $css_var_name = '--' . str_replace('_', '-', $row['setting_name']);
        $css_value = $row['setting_value'];
        
        // Add units where needed
        if (preg_match('/^(font_size|padding|margin|width|height)/', $row['setting_name']) && is_numeric($css_value)) {
            $css_value .= 'px';
        }
        
        $css .= "    {$css_var_name}: {$css_value};\n";
    }
    
    $css .= "}\n";
    
    return $css;
}

/**
 * Load Google Fonts
 */
function loadGoogleFonts($conn) {
    $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_name = 'google_fonts'");
    $stmt->execute();
    $result = $stmt->get_result();
    $fonts = $result->fetch_assoc();
    
    if ($fonts && !empty($fonts['setting_value'])) {
        return '<link href="https://fonts.googleapis.com/css2?family=' . urlencode($fonts['setting_value']) . '&display=swap" rel="stylesheet">';
    }
    
    return '';
}

/**
 * Get maintenance mode status
 */
function isMaintenanceMode($conn) {
    $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_name = 'maintenance_mode'");
    $stmt->execute();
    $result = $stmt->get_result();
    $setting = $result->fetch_assoc();
    
    return $setting && $setting['setting_value'] === '1';
}

/**
 * Generate sitemap XML
 */
function generateSitemap($conn) {
    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    $base_url = getCurrentUrl();
    
    // Add homepage
    $sitemap .= '  <url>' . "\n";
    $sitemap .= '    <loc>' . $base_url . '/</loc>' . "\n";
    $sitemap .= '    <changefreq>daily</changefreq>' . "\n";
    $sitemap .= '    <priority>1.0</priority>' . "\n";
    $sitemap .= '  </url>' . "\n";
    
    // Add categories
    $stmt = $conn->prepare("SELECT slug, updated_at FROM categories WHERE is_active = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($category = $result->fetch_assoc()) {
        $sitemap .= '  <url>' . "\n";
        $sitemap .= '    <loc>' . $base_url . '/category/' . urlencode($category['slug']) . '</loc>' . "\n";
        $sitemap .= '    <lastmod>' . date('Y-m-d', strtotime($category['updated_at'])) . '</lastmod>' . "\n";
        $sitemap .= '    <changefreq>weekly</changefreq>' . "\n";
        $sitemap .= '    <priority>0.8</priority>' . "\n";
        $sitemap .= '  </url>' . "\n";
    }
    
    // Add products
    $stmt = $conn->prepare("SELECT slug, updated_at FROM products WHERE is_active = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($product = $result->fetch_assoc()) {
        $sitemap .= '  <url>' . "\n";
        $sitemap .= '    <loc>' . $base_url . '/product/' . urlencode($product['slug']) . '</loc>' . "\n";
        $sitemap .= '    <lastmod>' . date('Y-m-d', strtotime($product['updated_at'])) . '</lastmod>' . "\n";
        $sitemap .= '    <changefreq>monthly</changefreq>' . "\n";
        $sitemap .= '    <priority>0.6</priority>' . "\n";
        $sitemap .= '  </url>' . "\n";
    }
    
    $sitemap .= '</urlset>';
    
    return $sitemap;
}

/**
 * Generate robots.txt content
 */
function generateRobotsTxt() {
    $robots = "User-agent: *\n";
    $robots .= "Allow: /\n";
    $robots .= "Disallow: /admin/\n";
    $robots .= "Disallow: /config/\n";
    $robots .= "Disallow: /ajax/\n";
    $robots .= "Disallow: /logs/\n";
    $robots .= "\n";
    $robots .= "Sitemap: " . getCurrentUrl() . "/sitemap.xml\n";
    
    return $robots;
}

/**
 * Minify CSS content
 */
function minifyCSS($css) {
    // Remove comments
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    
    // Remove unnecessary whitespace
    $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
    $css = str_replace(['; ', ' ;', ' {', '{ ', '} ', ' }', ': ', ' :'], [';', ';', '{', '{', '}', '}', ':', ':'], $css);
    
    return trim($css);
}

/**
 * Minify JavaScript content
 */
function minifyJS($js) {
    // Simple minification - remove comments and extra whitespace
    $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js); // Remove /* */ comments
    $js = preg_replace('/\/\/.*$/', '', $js); // Remove // comments
    $js = preg_replace('/\s+/', ' ', $js); // Replace multiple whitespace with single space
    $js = str_replace(['; ', ' ;', ' {', '{ ', '} ', ' }'], [';', ';', '{', '{', '}', '}'], $js);
    
    return trim($js);
}

/**
 * Get cache buster for static assets
 */
function getCacheBuster($file_path) {
    if (file_exists($file_path)) {
        return '?v=' . filemtime($file_path);
    }
    return '?v=' . time();
}

/**
 * Generate responsive image HTML
 */
function generateResponsiveImage($image_path, $alt_text = '', $classes = '', $lazy_load = true) {
    if (empty($image_path)) {
        return '';
    }
    
    $full_path = 'uploads/images/' . $image_path;
    $thumb_path = 'uploads/thumbnails/thumb_' . $image_path;
    
    $img_attrs = [
        'class' => $classes,
        'alt' => htmlspecialchars($alt_text)
    ];
    
    if ($lazy_load) {
        $img_attrs['loading'] = 'lazy';
        $img_attrs['data-src'] = $full_path;
        $img_attrs['src'] = file_exists($thumb_path) ? $thumb_path : $full_path;
    } else {
        $img_attrs['src'] = $full_path;
    }
    
    $attr_string = '';
    foreach ($img_attrs as $key => $value) {
        if (!empty($value)) {
            $attr_string .= ' ' . $key . '="' . $value . '"';
        }
    }
    
    return '<img' . $attr_string . '>';
}

/**
 * Get page loading animation
 */
function getPageLoader() {
    return '
    <div id="page-loader" class="page-loader">
        <div class="loader-content">
            <div class="spinner"></div>
            <p>Loading...</p>
        </div>
    </div>
    ';
}

/**
 * Generate scroll-to-top button
 */
function getScrollToTopButton() {
    return '
    <button id="scroll-to-top" class="scroll-to-top" aria-label="Scroll to top">
        <i class="fas fa-chevron-up"></i>
    </button>
    ';
}

/**
 * Get cookie consent banner
 */
function getCookieConsentBanner($conn) {
    $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_name = 'cookie_consent_enabled'");
    $stmt->execute();
    $result = $stmt->get_result();
    $setting = $result->fetch_assoc();
    
    if (!$setting || $setting['setting_value'] !== '1') {
        return '';
    }
    
    return '
    <div id="cookie-consent" class="cookie-consent" style="display: none;">
        <div class="cookie-content">
            <p>This website uses cookies to ensure you get the best experience on our website.</p>
            <div class="cookie-actions">
                <button id="accept-cookies" class="btn btn-primary">Accept</button>
                <button id="decline-cookies" class="btn btn-secondary">Decline</button>
            </div>
        </div>
    </div>
    ';
}

/**
 * Generate navigation menu from modules
 */
function generateNavigationMenu($conn) {
    $stmt = $conn->prepare("SELECT content_data FROM modules WHERE type = 'menu' AND is_active = 1 ORDER BY display_order LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $menu_module = $result->fetch_assoc();
    
    if (!$menu_module) {
        return '';
    }
    
    $menu_data = json_decode($menu_module['content_data'], true);
    $menu_items = $menu_data['menu_items'] ?? [];
    
    if (empty($menu_items)) {
        return '';
    }
    
    $nav_html = '<nav class="main-navigation" role="navigation">';
    $nav_html .= '<div class="nav-container">';
    $nav_html .= '<button class="mobile-menu-toggle" aria-label="Toggle menu">';
    $nav_html .= '<span class="hamburger"></span>';
    $nav_html .= '</button>';
    $nav_html .= '<ul class="nav-menu">';
    
    foreach ($menu_items as $item) {
        $nav_html .= '<li class="nav-item">';
        $nav_html .= '<a href="' . htmlspecialchars($item['url']) . '" class="nav-link"';
        
        if (!empty($item['target'])) {
            $nav_html .= ' target="' . htmlspecialchars($item['target']) . '"';
        }
        
        $nav_html .= '>' . htmlspecialchars($item['text']) . '</a>';
        $nav_html .= '</li>';
    }
    
    $nav_html .= '</ul>';
    $nav_html .= '</div>';
    $nav_html .= '</nav>';
    
    return $nav_html;
}

/**
 * Generate meta tags for SEO
 */
function generateSEOMetaTags($conn, $page_title = '', $page_description = '', $keywords = '') {
    // Get site settings
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM site_settings WHERE setting_name IN ('site_name', 'site_description', 'default_keywords')");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_name']] = $row['setting_value'];
    }
    
    $site_name = $settings['site_name'] ?? 'CMS Website';
    $site_description = $settings['site_description'] ?? '';
    $default_keywords = $settings['default_keywords'] ?? '';
    
    $title = !empty($page_title) ? $page_title . ' | ' . $site_name : $site_name;
    $description = !empty($page_description) ? $page_description : $site_description;
    $meta_keywords = !empty($keywords) ? $keywords : $default_keywords;
    
    $meta_tags = '';
    $meta_tags .= '<title>' . htmlspecialchars($title) . '</title>' . "\n";
    $meta_tags .= '<meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
    
    if (!empty($meta_keywords)) {
        $meta_tags .= '<meta name="keywords" content="' . htmlspecialchars($meta_keywords) . '">' . "\n";
    }
    
    $meta_tags .= '<meta name="robots" content="index, follow">' . "\n";
    $meta_tags .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
    $meta_tags .= '<meta name="author" content="' . htmlspecialchars($site_name) . '">' . "\n";
    $meta_tags .= '<link rel="canonical" href="' . getCurrentUrl() . $_SERVER['REQUEST_URI'] . '">' . "\n";
    
    return $meta_tags;
}
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