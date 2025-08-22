<?php
/**
 * Custom HTML Module Renderer
 * Displays custom HTML content with background support
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

<div class="module-container custom-html-module-container" data-module-id="<?= e($moduleId) ?>">
    <div class="custom-html-module <?= $backgroundImage ? 'module-background' : '' ?>" <?= $styleAttr ?>>
        <?php if ($backgroundImage): ?>
            <div class="module-overlay" style="background: rgba(0,0,0,0.2); position: absolute; top: 0; left: 0; right: 0; bottom: 0; border-radius: 12px;"></div>
        <?php endif; ?>
        
        <div class="module-content" style="<?= $backgroundImage ? 'position: relative; z-index: 2;' : '' ?>">
            <?php if (!empty($title)): ?>
                <div class="module-title" style="margin-bottom: 20px; text-align: center;">
                    <h2><?= e($title) ?></h2>
                </div>
            <?php endif; ?>
            
            <div class="custom-html-content">
                <?= $content ?>
            </div>
        </div>
    </div>
</div>

<style>
.custom-html-module-container {
    margin: 30px 0;
}

.custom-html-module {
    width: 100%;
    padding: 20px;
    border-radius: 12px;
    position: relative;
    overflow: hidden;
}

.custom-html-module.module-background {
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.custom-html-content {
    width: 100%;
}

/* Reset styles for custom HTML content */
.custom-html-content * {
    max-width: 100%;
    box-sizing: border-box;
}

.custom-html-content img {
    height: auto;
    border-radius: 8px;
}

.custom-html-content iframe {
    max-width: 100%;
    border-radius: 8px;
}

.custom-html-content video {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
}

.custom-html-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.custom-html-content table th,
.custom-html-content table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.custom-html-content table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.custom-html-content pre {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    overflow-x: auto;
    margin: 20px 0;
    border-left: 4px solid var(--links-color);
}

.custom-html-content code {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
}

.custom-html-content pre code {
    background: none;
    padding: 0;
}

/* Form styling for custom HTML */
.custom-html-content form {
    margin: 20px 0;
}

.custom-html-content input,
.custom-html-content textarea,
.custom-html-content select {
    width: 100%;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 15px;
    transition: border-color 0.3s ease;
}

.custom-html-content input:focus,
.custom-html-content textarea:focus,
.custom-html-content select:focus {
    outline: none;
    border-color: var(--links-color);
}

.custom-html-content button,
.custom-html-content input[type="submit"] {
    background: var(--links-color);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s ease;
    width: auto;
    margin-bottom: 0;
}

.custom-html-content button:hover,
.custom-html-content input[type="submit"]:hover {
    background: #0056b3;
    transform: translateY(-2px);
}

/* List styling */
.custom-html-content ul,
.custom-html-content ol {
    padding-left: 30px;
    margin: 15px 0;
}

.custom-html-content li {
    margin-bottom: 8px;
}

/* Blockquote styling */
.custom-html-content blockquote {
    border-left: 4px solid var(--links-color);
    padding-left: 20px;
    margin: 20px 0;
    font-style: italic;
    background: rgba(0, 123, 255, 0.05);
    padding: 15px 20px;
    border-radius: 0 8px 8px 0;
}

/* Link styling */
.custom-html-content a {
    color: var(--links-color);
    text-decoration: underline;
    transition: opacity 0.3s ease;
}

.custom-html-content a:hover {
    opacity: 0.8;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .custom-html-module {
        padding: 15px;
        margin: 15px 10px;
    }
    
    .custom-html-content table {
        font-size: 14px;
    }
    
    .custom-html-content table th,
    .custom-html-content table td {
        padding: 8px;
    }
}

@media (max-width: 480px) {
    .custom-html-module {
        padding: 10px;
        margin: 10px 5px;
    }
    
    .custom-html-content pre {
        font-size: 12px;
        padding: 10px;
    }
    
    .custom-html-content table {
        font-size: 12px;
    }
    
    .custom-html-content table th,
    .custom-html-content table td {
        padding: 6px;
    }
}

/* Print styles */
@media print {
    .custom-html-content button,
    .custom-html-content input[type="submit"] {
        display: none;
    }
}
</style>