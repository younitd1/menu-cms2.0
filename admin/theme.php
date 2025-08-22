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
        
        updateThemeSettings($_POST, $_FILES);
        redirect('theme.php', 'Theme settings updated successfully!', 'success');
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get current theme settings
$themeSettings = getThemeSettings();

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
    <title>Theme Settings - CMS Admin</title>
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
                    <h1 class="page-title">Theme Settings</h1>
                    <div class="page-actions">
                        <a href="../index.php" target="_blank" class="btn btn-outline">
                            <i class="fas fa-external-link-alt"></i> Preview Site
                        </a>
                    </div>
                </div>
                
                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?= e($flashMessage['type']) ?>">
                        <?= e($flashMessage['message']) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e(CSRFProtection::generateToken()) ?>">
                    
                    <!-- Background Settings -->
                    <div class="form-container">
                        <h3><i class="fas fa-image"></i> Background Settings</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Background Image</label>
                                <div class="file-upload-area" 
                                     data-max-size="307200" 
                                     data-allowed-types="image/jpeg">
                                    <input type="file" 
                                           name="full_bg_image" 
                                           accept="image/jpeg"
                                           style="display: none;">
                                    <div class="upload-text">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Click to upload background image</p>
                                        <small>JPG only, max 300KB</small>
                                    </div>
                                </div>
                                
                                <?php if (!empty($themeSettings['full_bg_image'])): ?>
                                    <div class="current-image mt-3">
                                        <label>Current Background:</label>
                                        <div class="image-preview">
                                            <img src="<?= UPLOADS_URL ?>/images/<?= e($themeSettings['full_bg_image']) ?>" 
                                                 alt="Current background" 
                                                 style="max-width: 300px; max-height: 150px; border-radius: 8px; object-fit: cover;">
                                            <br>
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm mt-2" 
                                                    onclick="deleteThemeImage('full_bg_image')">
                                                <i class="fas fa-trash"></i> Delete Image
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="full_bg_color">Full Background Color</label>
                                <input type="color" 
                                       id="full_bg_color" 
                                       name="full_bg_color" 
                                       class="form-control" 
                                       value="<?= e($themeSettings['full_bg_color'] ?? '#1a269b') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Logo Settings -->
                    <div class="form-container">
                        <h3><i class="fas fa-copyright"></i> Logo Settings</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Store Logo (Front-end)</label>
                                <div class="file-upload-area" 
                                     data-max-size="307200" 
                                     data-allowed-types="image/jpeg">
                                    <input type="file" 
                                           name="store_logo" 
                                           accept="image/jpeg"
                                           style="display: none;">
                                    <div class="upload-text">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Upload store logo</p>
                                        <small>JPG only, max 300KB</small>
                                    </div>
                                </div>
                                
                                <?php if (!empty($themeSettings['store_logo'])): ?>
                                    <div class="current-image mt-3">
                                        <label>Current Store Logo:</label>
                                        <div class="image-preview">
                                            <img src="<?= UPLOADS_URL ?>/logos/<?= e($themeSettings['store_logo']) ?>" 
                                                 alt="Store logo" 
                                                 style="max-width: 200px; max-height: 100px; border-radius: 8px; object-fit: contain;">
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label>Login Logo</label>
                                <div class="file-upload-area" 
                                     data-max-size="307200" 
                                     data-allowed-types="image/jpeg">
                                    <input type="file" 
                                           name="login_logo" 
                                           accept="image/jpeg"
                                           style="display: none;">
                                    <div class="upload-text">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Upload login logo</p>
                                        <small>JPG only, max 300KB</small>
                                    </div>
                                </div>
                                
                                <?php if (!empty($themeSettings['login_logo'])): ?>
                                    <div class="current-image mt-3">
                                        <label>Current Login Logo:</label>
                                        <div class="image-preview">
                                            <img src="<?= UPLOADS_URL ?>/logos/<?= e($themeSettings['login_logo']) ?>" 
                                                 alt="Login logo" 
                                                 style="max-width: 200px; max-height: 100px; border-radius: 8px; object-fit: contain;">
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label>Backend Logo</label>
                                <div class="file-upload-area" 
                                     data-max-size="307200" 
                                     data-allowed-types="image/jpeg">
                                    <input type="file" 
                                           name="backend_logo" 
                                           accept="image/jpeg"
                                           style="display: none;">
                                    <div class="upload-text">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Upload backend logo</p>
                                        <small>JPG only, max 300KB</small>
                                    </div>
                                </div>
                                
                                <?php if (!empty($themeSettings['backend_logo'])): ?>
                                    <div class="current-image mt-3">
                                        <label>Current Backend Logo:</label>
                                        <div class="image-preview">
                                            <img src="<?= UPLOADS_URL ?>/logos/<?= e($themeSettings['backend_logo']) ?>" 
                                                 alt="Backend logo" 
                                                 style="max-width: 200px; max-height: 100px; border-radius: 8px; object-fit: contain;">
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Typography Settings -->
                    <div class="form-container">
                        <h3><i class="fas fa-font"></i> Typography Settings</h3>
                        
                        <div class="form-group">
                            <label for="main_font">Main Font (Regular text, links, paragraphs)</label>
                            <select id="main_font" name="main_font" class="form-control form-select">
                                <option value="Arial, sans-serif" <?= ($themeSettings['main_font'] ?? '') === 'Arial, sans-serif' ? 'selected' : '' ?>>Arial</option>
                                <option value="Helvetica, sans-serif" <?= ($themeSettings['main_font'] ?? '') === 'Helvetica, sans-serif' ? 'selected' : '' ?>>Helvetica</option>
                                <option value="Georgia, serif" <?= ($themeSettings['main_font'] ?? '') === 'Georgia, serif' ? 'selected' : '' ?>>Georgia</option>
                                <option value="'Times New Roman', serif" <?= ($themeSettings['main_font'] ?? '') === "'Times New Roman', serif" ? 'selected' : '' ?>>Times New Roman</option>
                                <option value="'Courier New', monospace" <?= ($themeSettings['main_font'] ?? '') === "'Courier New', monospace" ? 'selected' : '' ?>>Courier New</option>
                                <option value="Verdana, sans-serif" <?= ($themeSettings['main_font'] ?? '') === 'Verdana, sans-serif' ? 'selected' : '' ?>>Verdana</option>
                                <option value="'Trebuchet MS', sans-serif" <?= ($themeSettings['main_font'] ?? '') === "'Trebuchet MS', sans-serif" ? 'selected' : '' ?>>Trebuchet MS</option>
                            </select>
                        </div>
                        
                        <!-- Heading Settings -->
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="heading-settings">
                                <h4>H<?= $i ?> Settings (<?= $i === 1 ? 'Category Titles' : ($i === 2 ? 'Product Titles' : ($i === 4 ? 'Category Descriptions' : ($i === 5 ? 'Product Descriptions' : 'General Heading'))) ?>)</h4>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="h<?= $i ?>_font">Font Family</label>
                                        <select id="h<?= $i ?>_font" name="h<?= $i ?>_font" class="form-control form-select">
                                            <option value="Arial, sans-serif" <?= ($themeSettings["h{$i}_font"] ?? '') === 'Arial, sans-serif' ? 'selected' : '' ?>>Arial</option>
                                            <option value="Helvetica, sans-serif" <?= ($themeSettings["h{$i}_font"] ?? '') === 'Helvetica, sans-serif' ? 'selected' : '' ?>>Helvetica</option>
                                            <option value="Georgia, serif" <?= ($themeSettings["h{$i}_font"] ?? '') === 'Georgia, serif' ? 'selected' : '' ?>>Georgia</option>
                                            <option value="'Times New Roman', serif" <?= ($themeSettings["h{$i}_font"] ?? '') === "'Times New Roman', serif" ? 'selected' : '' ?>>Times New Roman</option>
                                            <option value="'Courier New', monospace" <?= ($themeSettings["h{$i}_font"] ?? '') === "'Courier New', monospace" ? 'selected' : '' ?>>Courier New</option>
                                            <option value="Verdana, sans-serif" <?= ($themeSettings["h{$i}_font"] ?? '') === 'Verdana, sans-serif' ? 'selected' : '' ?>>Verdana</option>
                                            <option value="'Trebuchet MS', sans-serif" <?= ($themeSettings["h{$i}_font"] ?? '') === "'Trebuchet MS', sans-serif" ? 'selected' : '' ?>>Trebuchet MS</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="h<?= $i ?>_color">Color</label>
                                        <input type="color" 
                                               id="h<?= $i ?>_color" 
                                               name="h<?= $i ?>_color" 
                                               class="form-control" 
                                               value="<?= e($themeSettings["h{$i}_color"] ?? ($i <= 2 ? '#ffcc3f' : '#ffffff')) ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="h<?= $i ?>_size">Size (px)</label>
                                        <input type="number" 
                                               id="h<?= $i ?>_size" 
                                               name="h<?= $i ?>_size" 
                                               class="form-control" 
                                               value="<?= e($themeSettings["h{$i}_size"] ?? (32 - ($i * 4))) ?>"
                                               min="10" 
                                               max="72">
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                        
                        <div class="form-group">
                            <label for="links_color">Links Color</label>
                            <input type="color" 
                                   id="links_color" 
                                   name="links_color" 
                                   class="form-control" 
                                   value="<?= e($themeSettings['links_color'] ?? '#0066cc') ?>">
                        </div>
                    </div>
                    
                    <!-- Layout Settings -->
                    <div class="form-container">
                        <h3><i class="fas fa-th-large"></i> Layout Settings</h3>
                        
                        <div class="layout-settings-grid">
                            <div class="layout-section">
                                <h4>Product Spacing</h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="product_margin">Margin (px)</label>
                                        <input type="number" 
                                               id="product_margin" 
                                               name="product_margin" 
                                               class="form-control" 
                                               value="<?= e($themeSettings['product_margin'] ?? 20) ?>"
                                               min="0" 
                                               max="100">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="product_padding">Padding (px)</label>
                                        <input type="number" 
                                               id="product_padding" 
                                               name="product_padding" 
                                               class="form-control" 
                                               value="<?= e($themeSettings['product_padding'] ?? 15) ?>"
                                               min="0" 
                                               max="100">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="layout-section">
                                <h4>Category Spacing</h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="category_margin">Margin (px)</label>
                                        <input type="number" 
                                               id="category_margin" 
                                               name="category_margin" 
                                               class="form-control" 
                                               value="<?= e($themeSettings['category_margin'] ?? 20) ?>"
                                               min="0" 
                                               max="100">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="category_padding">Padding (px)</label>
                                        <input type="number" 
                                               id="category_padding" 
                                               name="category_padding" 
                                               class="form-control" 
                                               value="<?= e($themeSettings['category_padding'] ?? 15) ?>"
                                               min="0" 
                                               max="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Save Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Theme Settings
                        </button>
                        
                        <button type="button" class="btn btn-outline" onclick="resetToDefaults()">
                            <i class="fas fa-undo"></i> Reset to Defaults
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        // Delete theme image function
        function deleteThemeImage(imageType) {
            if (!confirm('Are you sure you want to delete this image?')) {
                return;
            }
            
            AdminCMS.ajax('ajax/delete_theme_image.php', {
                method: 'POST',
                body: JSON.stringify({
                    image_type: imageType
                })
            }).then(response => {
                if (response.success) {
                    location.reload();
                } else {
                    AdminCMS.showAlert('Failed to delete image', 'error');
                }
            });
        }
        
        // Reset to defaults
        function resetToDefaults() {
            if (!confirm('Are you sure you want to reset all theme settings to defaults? This action cannot be undone.')) {
                return;
            }
            
            AdminCMS.ajax('ajax/reset_theme_defaults.php', {
                method: 'POST',
                body: JSON.stringify({})
            }).then(response => {
                if (response.success) {
                    location.reload();
                } else {
                    AdminCMS.showAlert('Failed to reset settings', 'error');
                }
            });
        }
        
        // Live preview updates
        document.addEventListener('change', function(e) {
            if (e.target.type === 'color' || e.target.type === 'number') {
                updatePreview();
            }
        });
        
        function updatePreview() {
            // Create a preview window or update preview elements
            // This would show live changes to colors and fonts
        }
    </script>
    
    <style>
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .form-container h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-container h3 i {
            color: #007bff;
        }
        
        .heading-settings {
            margin: 25px 0;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .heading-settings h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
        }
        
        .layout-settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .layout-section {
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .layout-section h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
        }
        
        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }
        
        .file-upload-area:hover {
            border-color: #007bff;
        }
        
        .file-upload-area.dragover {
            border-color: #28a745;
            background: #f8fff8;
        }
        
        .upload-text i {
            font-size: 2rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .image-preview img {
            border: 1px solid #dee2e6;
        }
        
        .form-actions {
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        /* Color input styling */
        input[type="color"] {
            width: 60px;
            height: 40px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            cursor: pointer;
            background: none;
        }
        
        input[type="color"]::-webkit-color-swatch-wrapper {
            padding: 0;
        }
        
        input[type="color"]::-webkit-color-swatch {
            border: none;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .layout-settings-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>

<?php
/**
 * Theme Settings Functions
 */

function getThemeSettings() {
    $db = DB::getInstance();
    
    try {
        $stmt = $db->prepare("SELECT * FROM theme_settings LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch();
        
        if (!$settings) {
            // Create default settings if none exist
            $stmt = $db->prepare("INSERT INTO theme_settings (id) VALUES (1)");
            $stmt->execute();
            
            $stmt = $db->prepare("SELECT * FROM theme_settings LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch();
        }
        
        return $settings;
    } catch (Exception $e) {
        ErrorHandler::logError("Failed to get theme settings: " . $e->getMessage());
        return [];
    }
}

function updateThemeSettings($data, $files) {
    $db = DB::getInstance();
    
    // Get current settings
    $currentSettings = getThemeSettings();
    
    // Validate colors
    $fullBgColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $data['full_bg_color'] ?? '') ? $data['full_bg_color'] : '#1a269b';
    $linksColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $data['links_color'] ?? '') ? $data['links_color'] : '#0066cc';
    
    // Validate fonts
    $allowedFonts = [
        'Arial, sans-serif',
        'Helvetica, sans-serif', 
        'Georgia, serif',
        "'Times New Roman', serif",
        "'Courier New', monospace",
        'Verdana, sans-serif',
        "'Trebuchet MS', sans-serif"
    ];
    
    $mainFont = in_array($data['main_font'] ?? '', $allowedFonts) ? $data['main_font'] : 'Arial, sans-serif';
    
    // Validate heading settings
    $headingData = [];
    for ($i = 1; $i <= 5; $i++) {
        $headingData["h{$i}_font"] = in_array($data["h{$i}_font"] ?? '', $allowedFonts) ? $data["h{$i}_font"] : 'Arial, sans-serif';
        $headingData["h{$i}_color"] = preg_match('/^#[0-9A-Fa-f]{6}$/', $data["h{$i}_color"] ?? '') ? $data["h{$i}_color"] : ($i <= 2 ? '#ffcc3f' : '#ffffff');
        $headingData["h{$i}_size"] = InputValidator::validateInteger($data["h{$i}_size"] ?? (32 - ($i * 4)), 10, 72);
    }
    
    // Validate layout settings
    $productMargin = InputValidator::validateInteger($data['product_margin'] ?? 20, 0, 100);
    $productPadding = InputValidator::validateInteger($data['product_padding'] ?? 15, 0, 100);
    $categoryMargin = InputValidator::validateInteger($data['category_margin'] ?? 20, 0, 100);
    $categoryPadding = InputValidator::validateInteger($data['category_padding'] ?? 15, 0, 100);
    
    // Handle logo uploads
    $logoUploads = handleLogoUploads($files, $currentSettings);
    
    // Handle background image upload
    $fullBgImage = $currentSettings['full_bg_image'] ?? null;
    if (!empty($files['full_bg_image']['name'])) {
        // Delete old image if exists
        if ($fullBgImage) {
            @unlink(UPLOAD_IMAGES_PATH . '/' . $fullBgImage);
            @unlink(UPLOAD_THUMBNAILS_PATH . '/' . pathinfo($fullBgImage, PATHINFO_FILENAME) . '_thumb.' . pathinfo($fullBgImage, PATHINFO_EXTENSION));
        }
        
        $fullBgImage = uploadImage($files['full_bg_image'], UPLOAD_IMAGES_PATH, CATEGORY_BACKGROUND_WIDTH);
        generateThumbnail(UPLOAD_IMAGES_PATH . '/' . $fullBgImage, UPLOAD_THUMBNAILS_PATH);
    }
    
    // Update theme settings
    $stmt = $db->prepare("
        UPDATE theme_settings SET 
            full_bg_image = ?,
            full_bg_color = ?,
            store_logo = ?,
            login_logo = ?,
            backend_logo = ?,
            main_font = ?,
            h1_font = ?, h1_color = ?, h1_size = ?,
            h2_font = ?, h2_color = ?, h2_size = ?,
            h3_font = ?, h3_color = ?, h3_size = ?,
            h4_font = ?, h4_color = ?, h4_size = ?,
            h5_font = ?, h5_color = ?, h5_size = ?,
            links_color = ?,
            product_margin = ?, product_padding = ?,
            category_margin = ?, category_padding = ?,
            updated_at = NOW()
        WHERE id = 1
    ");
    
    $stmt->execute([
        $fullBgImage,
        $fullBgColor,
        $logoUploads['store_logo'],
        $logoUploads['login_logo'], 
        $logoUploads['backend_logo'],
        $mainFont,
        $headingData['h1_font'], $headingData['h1_color'], $headingData['h1_size'],
        $headingData['h2_font'], $headingData['h2_color'], $headingData['h2_size'],
        $headingData['h3_font'], $headingData['h3_color'], $headingData['h3_size'],
        $headingData['h4_font'], $headingData['h4_color'], $headingData['h4_size'],
        $headingData['h5_font'], $headingData['h5_color'], $headingData['h5_size'],
        $linksColor,
        $productMargin, $productPadding,
        $categoryMargin, $categoryPadding
    ]);
    
    // Clear theme cache
    clearCache('theme_*');
}

function handleLogoUploads($files, $currentSettings) {
    $logoFields = ['store_logo', 'login_logo', 'backend_logo'];
    $uploads = [];
    
    // Create logos directory if it doesn't exist
    $logoDir = UPLOADS_PATH . '/logos';
    if (!is_dir($logoDir)) {
        mkdir($logoDir, 0755, true);
    }
    
    foreach ($logoFields as $field) {
        $uploads[$field] = $currentSettings[$field] ?? null;
        
        if (!empty($files[$field]['name'])) {
            // Delete old logo if exists
            if ($uploads[$field]) {
                @unlink($logoDir . '/' . $uploads[$field]);
            }
            
            try {
                $filename = uniqid() . '_' . basename($files[$field]['name']);
                $filepath = $logoDir . '/' . $filename;
                
                InputValidator::validateFileUpload($files[$field], ALLOWED_IMAGE_TYPES, MAX_FILE_SIZE);
                
                if (move_uploaded_file($files[$field]['tmp_name'], $filepath)) {
                    // Resize logo to reasonable size
                    resizeImage($filepath, 400);
                    $uploads[$field] = $filename;
                } else {
                    throw new Exception("Failed to upload {$field}");
                }
            } catch (Exception $e) {
                ErrorHandler::logError("Logo upload failed for {$field}: " . $e->getMessage());
                // Continue with other uploads
            }
        }
    }
    
    return $uploads;
}
?>