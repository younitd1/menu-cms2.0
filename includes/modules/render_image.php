<?php
/**
 * Image/Slideshow Module Renderer
 * Displays single image or slideshow with click tracking
 */

// Module data is available in $module variable
$moduleId = $module['id'];
$title = $module['title'];
$backgroundColor = $module['background_color'] ?? '';
$backgroundImage = $module['background_image'] ?? '';
$clickTracking = $module['click_tracking'] ?? 'no';

// Decode module content settings
$settings = json_decode($module['content'], true) ?: [];
$width = $settings['width'] ?? '100%';
$asBackground = $settings['as_background'] ?? false;
$backgroundSize = $settings['background_size'] ?? 'cover';
$backgroundPosition = $settings['background_position'] ?? 'center center';
$slideshowEnabled = $settings['slideshow_enabled'] ?? false;

// Get module images
$db = DB::getInstance();
$stmt = $db->prepare("SELECT * FROM module_images WHERE module_id = ? ORDER BY image_order ASC");
$stmt->execute([$moduleId]);
$images = $stmt->fetchAll();

if (empty($images)) {
    return; // No images to display
}

// Build container style attributes
$containerStyles = [];
if ($backgroundColor) {
    $containerStyles[] = "background-color: {$backgroundColor}";
}
if ($backgroundImage) {
    $containerStyles[] = "background-image: url('" . UPLOADS_URL . "/images/{$backgroundImage}')";
    $containerStyles[] = "background-size: cover";
    $containerStyles[] = "background-position: center center";
    $containerStyles[] = "background-repeat: no-repeat";
}

$containerStyleAttr = !empty($containerStyles) ? 'style="' . implode('; ', $containerStyles) . '"' : '';
?>

<div class="module-container image-module-container" data-module-id="<?= e($moduleId) ?>">
    <div class="image-module <?= $backgroundImage ? 'module-background' : '' ?>" <?= $containerStyleAttr ?>>
        <?php if ($backgroundImage): ?>
            <div class="module-overlay" style="background: rgba(0,0,0,0.2); position: absolute; top: 0; left: 0; right: 0; bottom: 0; border-radius: 12px;"></div>
        <?php endif; ?>
        
        <div class="module-content" style="<?= $backgroundImage ? 'position: relative; z-index: 2;' : '' ?>">
            <?php if (!empty($title)): ?>
                <div class="module-title" style="margin-bottom: 20px; text-align: center;">
                    <h2><?= e($title) ?></h2>
                </div>
            <?php endif; ?>
            
            <?php if ($slideshowEnabled && count($images) > 1): ?>
                <!-- Slideshow Mode -->
                <div class="slideshow-container" 
                     data-autoplay-speed="5" 
                     data-click-tracking="<?= e($clickTracking) ?>"
                     onclick="<?= $clickTracking === 'yes' ? 'trackModuleClick(' . $moduleId . ')' : '' ?>">
                    
                    <?php foreach ($images as $index => $image): ?>
                        <div class="slide <?= $index === 0 ? 'active' : '' ?>">
                            <img src="<?= UPLOADS_URL ?>/images/<?= e($image['image_path']) ?>" 
                                 alt="<?= e($image['alt_text']) ?>" 
                                 loading="<?= $index === 0 ? 'eager' : 'lazy' ?>">
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Navigation arrows -->
                    <div class="slideshow-nav">
                        <button class="slideshow-prev" aria-label="Previous slide">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="slideshow-next" aria-label="Next slide">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    
                    <!-- Dots navigation -->
                    <div class="slideshow-dots">
                        <?php foreach ($images as $index => $image): ?>
                            <span class="slideshow-dot <?= $index === 0 ? 'active' : '' ?>" 
                                  data-slide="<?= $index ?>"></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Single Image Mode -->
                <?php $image = $images[0]; ?>
                
                <?php if ($asBackground): ?>
                    <div class="image-background-container" 
                         style="background-image: url('<?= UPLOADS_URL ?>/images/<?= e($image['image_path']) ?>');
                                background-size: <?= e($backgroundSize) ?>;
                                background-position: <?= e($backgroundPosition) ?>;
                                background-repeat: no-repeat;
                                height: 350px;
                                border-radius: 12px;
                                display: flex;
                                align-items: center;
                                justify-content: center;"
                         <?= $clickTracking === 'yes' ? 'onclick="trackModuleClick(' . $moduleId . ')" style="cursor: pointer;"' : '' ?>>
                        <!-- Background image content can go here -->
                    </div>
                <?php else: ?>
                    <div class="image-container width-<?= str_replace('%', '', $width) ?>" 
                         style="text-align: center;"
                         <?= $clickTracking === 'yes' ? 'onclick="trackModuleClick(' . $moduleId . ')" style="cursor: pointer;"' : '' ?>>
                        <img src="<?= UPLOADS_URL ?>/images/<?= e($image['image_path']) ?>" 
                             alt="<?= e($image['alt_text']) ?>" 
                             loading="lazy"
                             style="max-width: <?= e($width) ?>; height: auto; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);">
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Track module clicks for analytics
function trackModuleClick(moduleId) {
    fetch('ajax/track_click.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            click_type: 'module_click',
            module_id: moduleId
        })
    }).catch(error => {
        console.warn('Module click tracking failed:', error);
    });
}
</script>

<style>
.image-module-container {
    margin: 30px 0;
}

.image-module {
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.image-container {
    margin: 20px 0;
}

.image-background-container {
    position: relative;
    cursor: default;
}

/* Slideshow Styles */
.slideshow-container {
    position: relative;
    max-width: 100%;
    margin: 0 auto;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    background: #000;
}

.slide {
    display: none;
    position: relative;
}

.slide.active {
    display: block;
}

.slide img {
    width: 100%;
    height: 400px;
    object-fit: cover;
    display: block;
}

/* Navigation arrows */
.slideshow-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 100%;
    display: flex;
    justify-content: space-between;
    padding: 0 20px;
    pointer-events: none;
}

.slideshow-prev,
.slideshow-next {
    background: rgba(255, 255, 255, 0.8);
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    color: #333;
    transition: all 0.3s ease;
    pointer-events: auto;
    display: flex;
    align-items: center;
    justify-content: center;
}

.slideshow-prev:hover,
.slideshow-next:hover {
    background: white;
    transform: scale(1.1);
}

/* Dots navigation */
.slideshow-dots {
    text-align: center;
    padding: 20px;
    background: rgba(0, 0, 0, 0.8);
}

.slideshow-dot {
    height: 12px;
    width: 12px;
    margin: 0 6px;
    background-color: rgba(255, 255, 255, 0.5);
    border-radius: 50%;
    display: inline-block;
    cursor: pointer;
    transition: all 0.3s ease;
}

.slideshow-dot:hover,
.slideshow-dot.active {
    background-color: white;
    transform: scale(1.3);
}

/* Width classes for single images */
.width-100 img {
    max-width: 100%;
}

.width-75 img {
    max-width: 75%;
}

.width-50 img {
    max-width: 50%;
}

.width-25 img {
    max-width: 25%;
}

/* Click tracking cursor */
[data-click-tracking="yes"] {
    cursor: pointer;
}

[data-click-tracking="yes"]:hover {
    opacity: 0.9;
    transform: scale(1.02);
    transition: all 0.3s ease;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .slide img {
        height: 250px;
    }
    
    .slideshow-prev,
    .slideshow-next {
        width: 40px;
        height: 40px;
        font-size: 14px;
    }
    
    .slideshow-nav {
        padding: 0 10px;
    }
    
    .slideshow-dots {
        padding: 15px;
    }
    
    .slideshow-dot {
        height: 10px;
        width: 10px;
        margin: 0 4px;
    }
    
    .width-75 img,
    .width-50 img,
    .width-25 img {
        max-width: 100%;
    }
}

@media (max-width: 480px) {
    .slide img {
        height: 200px;
    }
    
    .slideshow-prev,
    .slideshow-next {
        width: 35px;
        height: 35px;
        font-size: 12px;
    }
    
    .image-background-container {
        height: 250px !important;
    }
}

/* Loading state */
.slide img {
    transition: opacity 0.3s ease;
}

.slide img[loading="lazy"] {
    opacity: 0;
}

.slide img[loading="lazy"].loaded {
    opacity: 1;
}

/* Accessibility */
.slideshow-prev:focus,
.slideshow-next:focus,
.slideshow-dot:focus {
    outline: 2px solid var(--links-color);
    outline-offset: 2px;
}

/* Print styles */
@media print {
    .slideshow-nav,
    .slideshow-dots {
        display: none;
    }
    
    .slide {
        display: block !important;
        page-break-inside: avoid;
        margin-bottom: 20px;
    }
}
</style>