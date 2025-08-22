<?php
/**
 * Rich Text Module Renderer
 * Displays rich text content with proper styling
 */

// Module data is available in $module variable
$moduleId = $module['id'];
$title = $module['title'];
$content = $module['content'];
$backgroundColor = $module['background_color'] ?? '';
$backgroundImage = $module['background_image'] ?? '';

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
?>

<div class="module-container rich-text-module-container" data-module-id="<?= e($moduleId) ?>">
    <div class="rich-text-module <?= $backgroundImage ? 'module-background' : '' ?>" <?= $styleAttr ?>>
        <?php if ($backgroundImage): ?>
            <div class="module-overlay" style="background: rgba(0,0,0,0.3); position: absolute; top: 0; left: 0; right: 0; bottom: 0; border-radius: 12px;"></div>
        <?php endif; ?>
        
        <div class="module-content" style="<?= $backgroundImage ? 'position: relative; z-index: 2;' : '' ?>">
            <?php if (!empty($title)): ?>
                <div class="module-title" style="margin-bottom: 20px;">
                    <h2><?= e($title) ?></h2>
                </div>
            <?php endif; ?>
            
            <div class="rich-text-content">
                <?= $content ?>
            </div>
        </div>
    </div>
</div>

<style>
.rich-text-module-container {
    margin: 30px 0;
}

.rich-text-module {
    max-width: 1000px;
    margin: 0 auto;
    padding: 30px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.rich-text-module.module-background {
    color: white;
    background-color: transparent !important;
}

.rich-text-content {
    line-height: 1.8;
}

.rich-text-content h1,
.rich-text-content h2,
.rich-text-content h3,
.rich-text-content h4,
.rich-text-content h5,
.rich-text-content h6 {
    margin-bottom: 20px;
    margin-top: 30px;
}

.rich-text-content h1:first-child,
.rich-text-content h2:first-child,
.rich-text-content h3:first-child,
.rich-text-content h4:first-child,
.rich-text-content h5:first-child,
.rich-text-content h6:first-child {
    margin-top: 0;
}

.rich-text-content p {
    margin-bottom: 20px;
}

.rich-text-content ul,
.rich-text-content ol {
    margin-bottom: 20px;
    padding-left: 30px;
}

.rich-text-content li {
    margin-bottom: 8px;
}

.rich-text-content a {
    color: var(--links-color);
    text-decoration: underline;
    transition: opacity 0.3s ease;
}

.rich-text-content a:hover {
    opacity: 0.8;
}

.rich-text-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.rich-text-content blockquote {
    border-left: 4px solid var(--links-color);
    padding-left: 20px;
    margin: 20px 0;
    font-style: italic;
    background: rgba(0, 123, 255, 0.05);
    padding: 15px 20px;
    border-radius: 0 8px 8px 0;
}

.rich-text-content strong {
    font-weight: 700;
}

.rich-text-content em {
    font-style: italic;
}

.rich-text-content u {
    text-decoration: underline;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .rich-text-module {
        padding: 20px;
        margin: 20px 10px;
    }
    
    .rich-text-content ul,
    .rich-text-content ol {
        padding-left: 20px;
    }
}

@media (max-width: 480px) {
    .rich-text-module {
        padding: 15px;
        margin: 15px 5px;
    }
}
</style>