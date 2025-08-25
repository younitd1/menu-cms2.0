<?php
require_once '../config/config.php';

// Check if user is logged in (matching your other files' pattern)
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get current user info if needed
$currentUser = [
    'id' => $_SESSION['user_id'] ?? null,
    'username' => $_SESSION['username'] ?? null,
    'full_name' => $_SESSION['full_name'] ?? 'User'
];

// Handle actions
$action = $_GET['action'] ?? 'list';
$moduleId = $_GET['id'] ?? null;
$moduleType = $_GET['type'] ?? null;
$message = '';
$messageType = 'info';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token (if you have CSRF protection)
        if (isset($_POST['csrf_token'])) {
            // Add CSRF validation here if you have it implemented
            // For now, we'll skip this validation
        }
        
        switch ($action) {
            case 'add':
                $moduleId = createModule($_POST, $_FILES);
                redirect("modules.php?action=edit&id={$moduleId}", 'Module created successfully!', 'success');
                break;
                
            case 'edit':
                updateModule($moduleId, $_POST, $_FILES);
                redirect("modules.php?action=edit&id={$moduleId}", 'Module updated successfully!', 'success');
                break;
                
            case 'delete':
                deleteModule($moduleId);
                redirect('modules.php', 'Module deleted successfully!', 'success');
                break;
                
            case 'toggle_status':
                toggleModuleStatus($moduleId);
                echo json_encode(['success' => true]);
                exit;
                
            case 'update_order':
                updateModuleOrder($_POST);
                echo json_encode(['success' => true]);
                exit;
        }
    } catch (Exception $e) {
        if ($action === 'toggle_status' || $action === 'update_order') {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Handle single delete
if ($action === 'delete' && $moduleId && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        deleteModule($moduleId);
        redirect('modules.php', 'Module deleted successfully!', 'success');
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get data based on action
$module = null;
$modules = [];
$categories = [];

if ($action === 'edit' && $moduleId) {
    $module = getModule($moduleId);
    if (!$module) {
        redirect('modules.php', 'Module not found.', 'error');
    }
}

if ($action === 'list') {
    $modules = getModulesGroupedByPosition();
}

if ($action === 'add' || $action === 'edit') {
    $categories = getCategoriesForMenu();
}

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
    <title>Modules - CMS Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="<?= htmlspecialchars(bin2hex(random_bytes(32))) ?>">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content-area">
                <?php if ($action === 'list'): ?>
                    <div class="page-header">
                        <h1 class="page-title">Modules</h1>
                        <div class="page-actions">
                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" type="button" id="addModuleDropdown">
                                    <i class="fas fa-plus"></i> Add New Module
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="modules.php?action=add&type=rich_text">
                                        <i class="fas fa-text-width"></i> Rich Text
                                    </a>
                                    <a class="dropdown-item" href="modules.php?action=add&type=custom_html">
                                        <i class="fas fa-code"></i> Custom HTML
                                    </a>
                                    <a class="dropdown-item" href="modules.php?action=add&type=image">
                                        <i class="fas fa-image"></i> Image / Slideshow
                                    </a>
                                    <a class="dropdown-item" href="modules.php?action=add&type=menu">
                                        <i class="fas fa-bars"></i> Menu
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($flashMessage): ?>
                        <div class="alert alert-<?= e($flashMessage['type']) ?>">
                            <?= e($flashMessage['message']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Modules organized by position -->
                    <div class="modules-container">
                        <?php foreach (MODULE_POSITIONS as $positionKey => $positionLabel): ?>
                            <div class="position-section">
                                <h3 class="position-title"><?= e($positionLabel) ?></h3>
                                <div class="modules-list sortable-list" data-position="<?= e($positionKey) ?>" data-update-url="modules.php?action=update_order">
                                    <?php if (isset($modules[$positionKey]) && !empty($modules[$positionKey])): ?>
                                        <?php foreach ($modules[$positionKey] as $module): ?>
                                            <div class="module-item sortable-item" data-item-id="<?= e($module['id']) ?>">
                                                <div class="module-drag-handle">
                                                    <i class="fas fa-grip-vertical"></i>
                                                </div>
                                                
                                                <div class="module-content">
                                                    <div class="module-header">
                                                        <h4 class="module-title"><?= e($module['title']) ?></h4>
                                                        <span class="module-type-badge"><?= e(ucfirst(str_replace('_', ' ', $module['module_type']))) ?></span>
                                                    </div>
                                                    
                                                    <p class="module-description">
                                                        <?= e(substr($module['description'] ?? '', 0, 100)) ?>
                                                        <?= strlen($module['description'] ?? '') > 100 ? '...' : '' ?>
                                                    </p>
                                                    
                                                    <div class="module-actions">
                                                        <button type="button" 
                                                                class="btn btn-sm toggle-status-btn <?= $module['status'] === 'active' ? 'btn-success' : 'btn-secondary' ?>"
                                                                onclick="toggleModuleStatus(<?= e($module['id']) ?>, '<?= e($module['status']) ?>')">
                                                            <i class="fas fa-<?= $module['status'] === 'active' ? 'toggle-on' : 'toggle-off' ?>"></i>
                                                            <?= $module['status'] === 'active' ? 'ON' : 'OFF' ?>
                                                        </button>
                                                        
                                                        <a href="modules.php?action=edit&id=<?= e($module['id']) ?>" class="btn btn-sm btn-secondary">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                        
                                                        <a href="modules.php?action=delete&id=<?= e($module['id']) ?>" 
                                                           class="btn btn-sm btn-danger" 
                                                           data-confirm="Are you sure you want to delete this module?">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-position">
                                            <p>No modules in this position</p>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline dropdown-toggle" type="button">
                                                    Add Module Here
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="modules.php?action=add&type=rich_text&position=<?= e($positionKey) ?>">Rich Text</a>
                                                    <a class="dropdown-item" href="modules.php?action=add&type=custom_html&position=<?= e($positionKey) ?>">Custom HTML</a>
                                                    <a class="dropdown-item" href="modules.php?action=add&type=image&position=<?= e($positionKey) ?>">Image / Slideshow</a>
                                                    <a class="dropdown-item" href="modules.php?action=add&type=menu&position=<?= e($positionKey) ?>">Menu</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                
                <?php elseif ($action === 'add' || $action === 'edit'): ?>
                    <div class="page-header">
                        <h1 class="page-title">
                            <?= $action === 'add' ? 'Add New ' . ucfirst(str_replace('_', ' ', $moduleType)) . ' Module' : 'Edit Module' ?>
                        </h1>
                        <div class="page-actions">
                            <a href="modules.php" class="btn btn-outline">
                                <i class="fas fa-arrow-left"></i> Back to Modules
                            </a>
                        </div>
                    </div>
                    
                    <?php if ($flashMessage): ?>
                        <div class="alert alert-<?= e($flashMessage['type']) ?>">
                            <?= e($flashMessage['message']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data" id="moduleForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(bin2hex(random_bytes(32))) ?>">
                            <input type="hidden" name="module_type" value="<?= e($moduleType ?? $module['module_type']) ?>">
                            
                            <!-- Common Fields -->
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="title">Module Title *</label>
                                    <input type="text" 
                                           id="title" 
                                           name="title" 
                                           class="form-control" 
                                           value="<?= e($module['title'] ?? '') ?>"
                                           required 
                                           maxlength="50">
                                </div>
                                
                                <div class="form-group">
                                    <label for="position">Module Position *</label>
                                    <select id="position" name="position" class="form-control form-select" required>
                                        <?php foreach (MODULE_POSITIONS as $posKey => $posLabel): ?>
                                            <option value="<?= e($posKey) ?>" 
                                                    <?= ($module['position'] ?? $_GET['position'] ?? '') === $posKey ? 'selected' : '' ?>>
                                                <?= e($posLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select id="status" name="status" class="form-control form-select">
                                        <option value="active" <?= ($module['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= ($module['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" 
                                          name="description" 
                                          class="form-control" 
                                          rows="3"
                                          maxlength="500"><?= e($module['description'] ?? '') ?></textarea>
                            </div>
                            
                            <!-- Module Type Specific Fields -->
                            <div class="module-fields">
                                <?php 
                                $currentType = $moduleType ?? $module['module_type'] ?? '';
                                if (file_exists("includes/module_fields_{$currentType}.php")) {
                                    include "includes/module_fields_{$currentType}.php";
                                }
                                ?>
                            </div>
                            
                            <!-- Background Options -->
                            <div class="form-section">
                                <h3>Background Options</h3>
                                
                                <div class="form-group">
                                    <label>Background Image</label>
                                    <div class="file-upload-area" 
                                         data-max-size="307200" 
                                         data-allowed-types="image/jpeg">
                                        <input type="file" 
                                               name="background_image" 
                                               accept="image/jpeg"
                                               style="display: none;">
                                        <div class="upload-text">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <p>Click to upload background image</p>
                                            <small>Image size: 1600x650px (Recommended) | Format: JPG | File size up to 300kb</small>
                                        </div>
                                    </div>
                                    
                                    <?php if ($action === 'edit' && !empty($module['background_image'])): ?>
                                        <div class="current-image mt-3">
                                            <label>Current Background Image:</label>
                                            <div class="image-preview">
                                                <img src="<?= UPLOADS_URL ?>/images/<?= e($module['background_image']) ?>" 
                                                     alt="Current background image" 
                                                     style="max-width: 400px; max-height: 200px; border-radius: 8px; object-fit: cover;">
                                                <br>
                                                <button type="button" 
                                                        class="btn btn-danger btn-sm mt-2" 
                                                        onclick="deleteModuleImage(<?= e($module['id']) ?>)">
                                                    <i class="fas fa-trash"></i> Delete Image
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="file-preview mt-3"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="background_color">Background Color</label>
                                    <input type="color" 
                                           id="background_color" 
                                           name="background_color" 
                                           class="form-control" 
                                           value="<?= e($module['background_color'] ?? '#ffffff') ?>">
                                </div>
                            </div>
                            
                            <div class="form-actions mt-4">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                
                                <button type="submit" name="save_and_close" value="1" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save and Close
                                </button>
                                
                                <a href="modules.php" class="btn btn-danger">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        // Toggle module status
        function toggleModuleStatus(moduleId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            AdminCMS.ajax('modules.php?action=toggle_status', {
                method: 'POST',
                body: JSON.stringify({
                    id: moduleId,
                    status: newStatus
                })
            }).then(response => {
                if (response.success) {
                    location.reload();
                } else {
                    AdminCMS.showAlert('Failed to update module status', 'error');
                }
            });
        }
        
        // Delete module image
        function deleteModuleImage(moduleId) {
            if (!confirm('Are you sure you want to delete this background image?')) {
                return;
            }
            
            AdminCMS.ajax('ajax/delete_module_image.php', {
                method: 'POST',
                body: JSON.stringify({
                    module_id: moduleId
                })
            }).then(response => {
                if (response.success) {
                    document.querySelector('.current-image').remove();
                    AdminCMS.showAlert('Background image deleted successfully!', 'success');
                } else {
                    AdminCMS.showAlert('Failed to delete image', 'error');
                }
            });
        }
        
        // Dropdown functionality
        document.addEventListener('click', function(e) {
            if (e.target.matches('.dropdown-toggle') || e.target.closest('.dropdown-toggle')) {
                const dropdown = e.target.closest('.dropdown');
                dropdown.classList.toggle('active');
            } else {
                document.querySelectorAll('.dropdown.active').forEach(d => d.classList.remove('active'));
            }
        });
        
        // Initialize sortable lists
        AdminCMS.initSortables();
    </script>
    
    <style>
        .modules-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .position-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .position-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .modules-list {
            min-height: 100px;
        }
        
        .module-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            transition: all 0.3s ease;
        }
        
        .module-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .module-item.dragging {
            opacity: 0.5;
        }
        
        .module-drag-handle {
            color: #6c757d;
            cursor: move;
            font-size: 16px;
            margin-top: 5px;
        }
        
        .module-content {
            flex: 1;
        }
        
        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .module-title {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .module-type-badge {
            background: #007bff;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .module-description {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 12px;
        }
        
        .module-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .toggle-status-btn {
            min-width: 60px;
        }
        
        .empty-position {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-position p {
            margin-bottom: 15px;
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-toggle::after {
            content: '▼';
            margin-left: 8px;
            font-size: 10px;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item {
            display: block;
            padding: 10px 15px;
            color: #495057;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        
        .dropdown-item:hover {
            background: #f8f9fa;
            text-decoration: none;
            color: #495057;
        }
        
        .dropdown-item i {
            width: 16px;
            margin-right: 8px;
        }
        
        .form-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .form-section h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
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
        
        .form-actions {
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .modules-container {
                grid-template-columns: 1fr;
            }
            
            .module-item {
                flex-direction: column;
                gap: 10px;
            }
            
            .module-actions {
                justify-content: center;
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
 * Module Management Functions
 */

function createModule($data, $files) {
    // Get database connection like in your other files
    $database = new Database();
    $conn = $database->getConnection();
    
    // Basic validation
    $title = $database->sanitizeInput($data['title']);
    $description = $database->sanitizeInput($data['description'] ?? '');
    $moduleType = in_array($data['module_type'], ['rich_text', 'custom_html', 'image', 'menu']) ? $data['module_type'] : 'rich_text';
    $position = isset($data['position']) ? $database->sanitizeInput($data['position']) : 'top-module-1';
    $status = in_array($data['status'] ?? 'active', ['active', 'inactive']) ? $data['status'] : 'active';
    $backgroundColor = $data['background_color'] ?? '#ffffff';
    
    if (empty($title)) {
        throw new Exception("Module title is required");
    }
    
    // Process module-specific content
    $content = processModuleContent($moduleType, $data, $files);
    
    // Get next display order
    $stmt = $conn->prepare("SELECT COALESCE(MAX(display_order), 0) + 1 as next_order FROM modules WHERE position = ?");
    $stmt->bind_param("s", $position);
    $stmt->execute();
    $result = $stmt->get_result();
    $displayOrder = $result->fetch_assoc()['next_order'];
    
    // Insert module
    $stmt = $conn->prepare("INSERT INTO modules (title, description, module_type, content, position, background_color, display_order, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssssss", $title, $description, $moduleType, $content, $position, $backgroundColor, $displayOrder, $status);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    } else {
        throw new Exception("Failed to create module");
    }
}

function updateModule($moduleId, $data, $files) {
    $db = DB::getInstance();
    $moduleId = InputValidator::validateInteger($moduleId, 1, null, true);
    
    // Get current module
    $stmt = $db->prepare("SELECT * FROM modules WHERE id = ?");
    $stmt->execute([$moduleId]);
    $currentModule = $stmt->fetch();
    
    if (!$currentModule) {
        throw new Exception("Module not found");
    }
    
    // Validate fields
    $title = InputValidator::validateText($data['title'], 50, true);
    $description = InputValidator::validateText($data['description'] ?? '', 500, false);
    $position = array_key_exists($data['position'], MODULE_POSITIONS) ? $data['position'] : $currentModule['position'];
    $status = in_array($data['status'] ?? 'active', ['active', 'inactive']) ? $data['status'] : 'active';
    $backgroundColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $data['background_color'] ?? '') ? $data['background_color'] : $currentModule['background_color'];
    
    // Handle background image upload
    $backgroundImage = $currentModule['background_image'];
    if (!empty($files['background_image']['name'])) {
        // Delete old image if exists
        if ($backgroundImage) {
            @unlink(UPLOAD_IMAGES_PATH . '/' . $backgroundImage);
            @unlink(UPLOAD_THUMBNAILS_PATH . '/' . pathinfo($backgroundImage, PATHINFO_FILENAME) . '_thumb.' . pathinfo($backgroundImage, PATHINFO_EXTENSION));
        }
        
        $backgroundImage = uploadImage($files['background_image'], UPLOAD_IMAGES_PATH, CATEGORY_BACKGROUND_WIDTH);
        generateThumbnail(UPLOAD_IMAGES_PATH . '/' . $backgroundImage, UPLOAD_THUMBNAILS_PATH);
    }
    
    // Process module-specific content
    $content = processModuleContent($currentModule['module_type'], $data, $files);
    
    // Update module
    $stmt = $db->prepare("
        UPDATE modules 
        SET title = ?, description = ?, content = ?, position = ?, background_image = ?, background_color = ?, status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$title, $description, $content, $position, $backgroundImage, $backgroundColor, $status, $moduleId]);
    
    // Handle module-specific additional data
    handleModuleSpecificData($moduleId, $currentModule['module_type'], $data, $files);
    
    // Redirect based on save action
    if (isset($data['save_and_close'])) {
        redirect('modules.php', 'Module updated successfully!', 'success');
    }
}

function processModuleContent($moduleType, $data, $files) {
    switch ($moduleType) {
        case 'rich_text':
            return InputValidator::validateHtmlContent($data['content'] ?? '');
            
        case 'custom_html':
            return $data['content'] ?? '';
            
        case 'image':
            $imageData = [
                'width' => $data['image_width'] ?? '100%',
                'as_background' => isset($data['as_background']),
                'background_size' => $data['background_size'] ?? 'cover',
                'background_position' => $data['background_position'] ?? 'center center',
                'slideshow_enabled' => isset($data['slideshow_enabled']),
                'click_tracking' => isset($data['click_tracking'])
            ];
            return json_encode($imageData);
            
        case 'menu':
            return ''; // Menu content is stored in menu_items table
            
        default:
            return '';
    }
}

function handleModuleSpecificData($moduleId, $moduleType, $data, $files) {
    $db = DB::getInstance();
    
    switch ($moduleType) {
        case 'image':
            // Handle slideshow images if enabled
            if (isset($data['slideshow_enabled'])) {
                handleSlideshowImages($moduleId, $files);
            } else {
                // Handle single image upload
                handleSingleImage($moduleId, $files);
            }
            break;
            
        case 'menu':
            // Handle menu items
            handleMenuItems($moduleId, $data);
            break;
    }
}

function handleSlideshowImages($moduleId, $files) {
    // Implementation for slideshow image uploads (up to 10 images)
    $db = DB::getInstance();
    
    // Clear existing images
    $stmt = $db->prepare("DELETE FROM module_images WHERE module_id = ?");
    $stmt->execute([$moduleId]);
    
    // Process new images
    for ($i = 1; $i <= 10; $i++) {
        if (!empty($files["slideshow_image_{$i}"]['name'])) {
            try {
                $imageName = uploadImage($files["slideshow_image_{$i}"], UPLOAD_IMAGES_PATH, PRODUCT_IMAGE_MAX_WIDTH);
                generateThumbnail(UPLOAD_IMAGES_PATH . '/' . $imageName, UPLOAD_THUMBNAILS_PATH);
                
                $stmt = $db->prepare("
                    INSERT INTO module_images (module_id, image_path, image_order, alt_text) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$moduleId, $imageName, $i, "Slideshow image {$i}"]);
            } catch (Exception $e) {
                // Log error but continue with other images
                ErrorHandler::logError("Failed to upload slideshow image {$i}: " . $e->getMessage());
            }
        }
    }
}

function handleSingleImage($moduleId, $files) {
    // Handle single image upload for image modules
    if (!empty($files['module_image']['name'])) {
        $db = DB::getInstance();
        
        // Clear existing images
        $stmt = $db->prepare("DELETE FROM module_images WHERE module_id = ?");
        $stmt->execute([$moduleId]);
        
        try {
            $imageName = uploadImage($files['module_image'], UPLOAD_IMAGES_PATH, PRODUCT_IMAGE_MAX_WIDTH);
            generateThumbnail(UPLOAD_IMAGES_PATH . '/' . $imageName, UPLOAD_THUMBNAILS_PATH);
            
            $stmt = $db->prepare("
                INSERT INTO module_images (module_id, image_path, image_order, alt_text) 
                VALUES (?, ?, 1, ?)
            ");
            $stmt->execute([$moduleId, $imageName, "Module image"]);
        } catch (Exception $e) {
            throw new Exception("Failed to upload image: " . $e->getMessage());
        }
    }
}

function handleMenuItems($moduleId, $data) {
    $db = DB::getInstance();
    
    // Clear existing menu items
    $stmt = $db->prepare("DELETE FROM menu_items WHERE module_id = ?");
    $stmt->execute([$moduleId]);
    
    // Add new menu items
    if (isset($data['menu_categories']) && is_array($data['menu_categories'])) {
        $stmt = $db->prepare("
            INSERT INTO menu_items (module_id, category_id, link_text, link_order, status) 
            VALUES (?, ?, ?, ?, 'active')
        ");
        
        foreach ($data['menu_categories'] as $order => $categoryId) {
            if (!empty($categoryId)) {
                // Get category title for link text
                $catStmt = $db->prepare("SELECT title FROM categories WHERE id = ?");
                $catStmt->execute([$categoryId]);
                $category = $catStmt->fetch();
                
                if ($category) {
                    $stmt->execute([$moduleId, $categoryId, $category['title'], $order + 1]);
                }
            }
        }
    }
}

function deleteModule($moduleId) {
    $db = DB::getInstance();
    $moduleId = InputValidator::validateInteger($moduleId, 1, null, true);
    
    // Get module to delete associated files
    $stmt = $db->prepare("SELECT * FROM modules WHERE id = ?");
    $stmt->execute([$moduleId]);
    $module = $stmt->fetch();
    
    if (!$module) {
        throw new Exception("Module not found");
    }
    
    // Delete background image if exists
    if ($module['background_image']) {
        @unlink(UPLOAD_IMAGES_PATH . '/' . $module['background_image']);
        @unlink(UPLOAD_THUMBNAILS_PATH . '/' . pathinfo($module['background_image'], PATHINFO_FILENAME) . '_thumb.' . pathinfo($module['background_image'], PATHINFO_EXTENSION));
    }
    
    // Delete module images
    $stmt = $db->prepare("SELECT image_path FROM module_images WHERE module_id = ?");
    $stmt->execute([$moduleId]);
    $images = $stmt->fetchAll();
    
    foreach ($images as $image) {
        @unlink(UPLOAD_IMAGES_PATH . '/' . $image['image_path']);
        @unlink(UPLOAD_THUMBNAILS_PATH . '/' . pathinfo($image['image_path'], PATHINFO_FILENAME) . '_thumb.' . pathinfo($image['image_path'], PATHINFO_EXTENSION));
    }
    
    // Delete module (CASCADE will handle related tables)
    $stmt = $db->prepare("DELETE FROM modules WHERE id = ?");
    $stmt->execute([$moduleId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Module not found");
    }
}

function toggleModuleStatus($moduleId) {
    $db = DB::getInstance();
    $moduleId = InputValidator::validateInteger($moduleId, 1, null, true);
    
    $stmt = $db->prepare("SELECT status FROM modules WHERE id = ?");
    $stmt->execute([$moduleId]);
    $module = $stmt->fetch();
    
    if (!$module) {
        throw new Exception("Module not found");
    }
    
    $newStatus = $module['status'] === 'active' ? 'inactive' : 'active';
    
    $stmt = $db->prepare("UPDATE modules SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $moduleId]);
}

function updateModuleOrder($data) {
    $db = DB::getInstance();
    
    if (isset($data['order']) && is_array($data['order'])) {
        $stmt = $db->prepare("UPDATE modules SET display_order = ? WHERE id = ?");
        
        foreach ($data['order'] as $moduleId => $order) {
            $moduleId = InputValidator::validateInteger($moduleId, 1);
            $order = InputValidator::validateInteger($order, 0);
            
            if ($moduleId && $order !== false) {
                $stmt->execute([$order, $moduleId]);
            }
        }
    }
}

// Simple redirect function if not in your config
if (!function_exists('redirect')) {
    function redirect($url, $message = '', $type = 'info') {
        if ($message) {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = $type;
        }
        header("Location: $url");
        exit;
    }
}

// Simple flash message function if not in your config  
if (!function_exists('getFlashMessage')) {
    function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = [
                'message' => $_SESSION['flash_message'],
                'type' => $_SESSION['flash_type'] ?? 'info'
            ];
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            return $message;
        }
        return null;
    }
}

// Simple HTML escaping function
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

function getModulesGroupedByPosition() {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM modules ORDER BY position, display_order ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $modules = $result->fetch_all(MYSQLI_ASSOC);
    
    $grouped = [];
    foreach ($modules as $module) {
        $grouped[$module['position']][] = $module;
    }
    
    return $grouped;
}

function getModule($moduleId) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM modules WHERE id = ?");
    $stmt->bind_param("i", $moduleId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function deleteModule($moduleId) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("DELETE FROM modules WHERE id = ?");
    $stmt->bind_param("i", $moduleId);
    
    if ($stmt->execute()) {
        return true;
    } else {
        throw new Exception("Failed to delete module");
    }
}

function toggleModuleStatus($moduleId) {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get current status
    $stmt = $conn->prepare("SELECT status FROM modules WHERE id = ?");
    $stmt->bind_param("i", $moduleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $module = $result->fetch_assoc();
    
    if (!$module) {
        throw new Exception("Module not found");
    }
    
    $newStatus = $module['status'] === 'active' ? 'inactive' : 'active';
    
    // Update status
    $stmt = $conn->prepare("UPDATE modules SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $moduleId);
    $stmt->execute();
}

function getCategoriesForMenu() {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("SELECT id, title FROM categories WHERE status = 'active' ORDER BY title");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
