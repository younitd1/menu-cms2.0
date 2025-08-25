<?php
require_once '../config/config.php';
SecureSession::requireLogin();

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

// Handle actions
$action = $_GET['action'] ?? 'list';
$moduleId = $_GET['id'] ?? null;
$moduleType = $_GET['type'] ?? null;
$message = '';
$messageType = 'info';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (isset($_POST['csrf_token'])) {
            if (!CSRFProtection::validateToken($_POST['csrf_token'])) {
                throw new Exception("Invalid CSRF token. Please try again.");
            }
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
            
            fetch('modules.php?action=toggle_status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    id: moduleId,
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to update module status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update module status');
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
    </script>
    
    <?php include 'includes/modules_styles.php'; ?>
</body>
</html>

<?php
/**
 * Module Management Functions - Fixed to use proper DB class
 */

function createModule($data, $files) {
    $db = DB::getInstance();
    
    // Basic validation
    $title = InputValidator::validateText($data['title'], 50, true);
    $description = InputValidator::validateText($data['description'] ?? '', 500, false);
    $moduleType = in_array($data['module_type'], ['rich_text', 'custom_html', 'image', 'menu']) ? $data['module_type'] : 'rich_text';
    $position = isset($data['position']) && array_key_exists($data['position'], MODULE_POSITIONS) ? $data['position'] : 'top-module-1';
    $status = in_array($data['status'] ?? 'active', ['active', 'inactive']) ? $data['status'] : 'active';
    $backgroundColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $data['background_color'] ?? '') ? $data['background_color'] : '#ffffff';
    
    // Process module-specific content
    $content = processModuleContent($moduleType, $data, $files);
    
    // Get next display order
    $stmt = $db->prepare("SELECT COALESCE(MAX(display_order), 0) + 1 as next_order FROM modules WHERE position = ?");
    $stmt->execute([$position]);
    $result = $stmt->fetch();
    $displayOrder = $result['next_order'];
    
    // Insert module
    $stmt = $db->prepare("INSERT INTO modules (title, description, module_type, content, position, background_color, display_order, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$title, $description, $moduleType, $content, $position, $backgroundColor, $displayOrder, $status]);
    
    return $db->lastInsertId();
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
    
    // Process module-specific content
    $content = processModuleContent($currentModule['module_type'], $data, $files);
    
    // Update module
    $stmt = $db->prepare("
        UPDATE modules 
        SET title = ?, description = ?, content = ?, position = ?, background_color = ?, status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$title, $description, $content, $position, $backgroundColor, $status, $moduleId]);
    
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

function getModulesGroupedByPosition() {
    $db = DB::getInstance();
    
    $stmt = $db->prepare("SELECT * FROM modules ORDER BY position, display_order ASC");
    $stmt->execute();
    $modules = $stmt->fetchAll();
    
    $grouped = [];
    foreach ($modules as $module) {
        $grouped[$module['position']][] = $module;
    }
    
    return $grouped;
}

function getModule($moduleId) {
    $db = DB::getInstance();
    $moduleId = InputValidator::validateInteger($moduleId, 1, null, true);
    
    $stmt = $db->prepare("SELECT * FROM modules WHERE id = ?");
    $stmt->execute([$moduleId]);
    return $stmt->fetch();
}

function getCategoriesForMenu() {
    $db = DB::getInstance();
    
    $stmt = $db->prepare("SELECT id, title FROM categories WHERE status = 'active' ORDER BY title");
    $stmt->execute();
    return $stmt->fetchAll();
}
?>
