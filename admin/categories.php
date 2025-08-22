<?php
require_once '../config/config.php';
SecureSession::requireLogin();

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

// Handle actions
$action = $_GET['action'] ?? 'list';
$categoryId = $_GET['id'] ?? null;
$message = '';
$messageType = 'info';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!CSRFProtection::validateToken($_POST['csrf_token'] ?? '')) {
            throw new Exception("Invalid request. Please try again.");
        }
        
        switch ($action) {
            case 'add':
                $categoryId = createCategory($_POST, $_FILES);
                redirect("categories.php?action=edit&id={$categoryId}", 'Category created successfully!', 'success');
                break;
                
            case 'edit':
                updateCategory($categoryId, $_POST, $_FILES);
                redirect("categories.php?action=edit&id={$categoryId}", 'Category updated successfully!', 'success');
                break;
                
            case 'delete':
                deleteCategory($categoryId);
                redirect('categories.php', 'Category deleted successfully!', 'success');
                break;
                
            case 'bulk_delete':
                $selectedIds = $_POST['selected_categories'] ?? [];
                if (!empty($selectedIds)) {
                    bulkDeleteCategories($selectedIds);
                    redirect('categories.php', count($selectedIds) . ' categories deleted successfully!', 'success');
                } else {
                    $message = 'Please select categories to delete.';
                    $messageType = 'warning';
                }
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Handle single delete
if ($action === 'delete' && $categoryId && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        deleteCategory($categoryId);
        redirect('categories.php', 'Category deleted successfully!', 'success');
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get data based on action
$category = null;
$categories = [];

if ($action === 'edit' && $categoryId) {
    $category = getCategory($categoryId);
    if (!$category) {
        redirect('categories.php', 'Category not found.', 'error');
    }
}

if ($action === 'list') {
    $page = $_GET['page'] ?? 1;
    $search = $_GET['search'] ?? '';
    $statusFilter = $_GET['status'] ?? '';
    $sortBy = $_GET['sort'] ?? 'created_at';
    $sortOrder = $_GET['order'] ?? 'DESC';
    
    $categories = getCategories($page, $search, $statusFilter, $sortBy, $sortOrder);
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
    <title>Categories - CMS Admin</title>
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
                <?php if ($action === 'list'): ?>
                    <div class="page-header">
                        <h1 class="page-title">Categories</h1>
                        <div class="page-actions">
                            <a href="categories.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New Category
                            </a>
                        </div>
                    </div>
                    
                    <?php if ($flashMessage): ?>
                        <div class="alert alert-<?= e($flashMessage['type']) ?>">
                            <?= e($flashMessage['message']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Filters -->
                    <div class="filters-section">
                        <form method="GET" class="filters-grid">
                            <input type="hidden" name="action" value="list">
                            
                            <div class="search-bar">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" class="form-control" placeholder="Search categories..." value="<?= e($search) ?>">
                            </div>
                            
                            <select name="status" class="form-control form-select">
                                <option value="">All Status</option>
                                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                            
                            <select name="sort" class="form-control form-select">
                                <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Date Created</option>
                                <option value="title" <?= $sortBy === 'title' ? 'selected' : '' ?>>Title</option>
                            </select>
                            
                            <button type="submit" class="btn btn-secondary">Filter</button>
                        </form>
                    </div>
                    
                    <!-- Categories List -->
                    <div class="table-container">
                        <form method="POST" action="categories.php?action=bulk_delete" id="bulkForm">
                            <input type="hidden" name="csrf_token" value="<?= e(CSRFProtection::generateToken()) ?>">
                            
                            <div class="table-header">
                                <div class="bulk-actions">
                                    <input type="checkbox" id="selectAll">
                                    <label for="selectAll">Select All</label>
                                    <button type="submit" class="btn btn-danger btn-sm" data-confirm="Are you sure you want to delete selected categories? This will also delete all products in these categories.">
                                        <i class="fas fa-trash"></i> Delete Selected
                                    </button>
                                </div>
                            </div>
                            
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAll">
                                        </th>
                                        <th width="80">Image</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th width="100">Products</th>
                                        <th width="100">Status</th>
                                        <th width="120">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories['data'] as $category): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="selected_categories[]" value="<?= e($category['id']) ?>" class="category-checkbox">
                                            </td>
                                            <td>
                                                <?php if ($category['background_image']): ?>
                                                    <img src="<?= UPLOADS_URL ?>/images/<?= e($category['background_image']) ?>" 
                                                         alt="<?= e($category['title']) ?>" 
                                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                                                <?php else: ?>
                                                    <div style="width: 60px; height: 60px; background: #f8f9fa; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= e($category['title']) ?></strong><br>
                                                <small class="text-muted">Slug: <?= e($category['slug']) ?></small>
                                            </td>
                                            <td>
                                                <?= e(substr($category['description'] ?? '', 0, 100)) ?>
                                                <?= strlen($category['description'] ?? '') > 100 ? '...' : '' ?>
                                            </td>
                                            <td>
                                                <span class="badge"><?= number_format($category['product_count'] ?? 0) ?></span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?= e($category['status']) ?>">
                                                    <?= ucfirst($category['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="categories.php?action=edit&id=<?= e($category['id']) ?>" class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="categories.php?action=delete&id=<?= e($category['id']) ?>" class="btn btn-sm btn-danger" data-confirm="Are you sure you want to delete this category? This will also delete all products in this category.">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </form>
                        
                        <!-- Pagination -->
                        <?php if ($categories['total_pages'] > 1): ?>
                            <div class="pagination">
                                <?php for ($i = 1; $i <= $categories['total_pages']; $i++): ?>
                                    <?php if ($i == $categories['current_page']): ?>
                                        <span class="current"><?= $i ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&sort=<?= urlencode($sortBy) ?>"><?= $i ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                
                <?php elseif ($action === 'add' || $action === 'edit'): ?>
                    <div class="page-header">
                        <h1 class="page-title"><?= $action === 'add' ? 'Add New Category' : 'Edit Category' ?></h1>
                        <div class="page-actions">
                            <a href="categories.php" class="btn btn-outline">
                                <i class="fas fa-arrow-left"></i> Back to Categories
                            </a>
                        </div>
                    </div>
                    
                    <?php if ($flashMessage): ?>
                        <div class="alert alert-<?= e($flashMessage['type']) ?>">
                            <?= e($flashMessage['message']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= e(CSRFProtection::generateToken()) ?>">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="title">Category Title *</label>
                                    <input type="text" 
                                           id="title" 
                                           name="title" 
                                           class="form-control" 
                                           value="<?= e($category['title'] ?? '') ?>"
                                           data-slug-from="#slug"
                                           required 
                                           maxlength="50">
                                </div>
                                
                                <div class="form-group">
                                    <label for="slug">Slug (URL) *</label>
                                    <input type="text" 
                                           id="slug" 
                                           name="slug" 
                                           class="form-control" 
                                           value="<?= e($category['slug'] ?? '') ?>"
                                           required
                                           maxlength="255">
                                    <small class="text-muted">Auto-generated from title if left empty. Used as div ID in front-end.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select id="status" name="status" class="form-control form-select">
                                        <option value="active" <?= ($category['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= ($category['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" 
                                          name="description" 
                                          class="form-control rich-text-editor" 
                                          rows="6"><?= e($category['description'] ?? '') ?></textarea>
                            </div>
                            
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
                                
                                <?php if ($action === 'edit' && !empty($category['background_image'])): ?>
                                    <div class="current-image mt-3">
                                        <label>Current Background Image:</label>
                                        <div class="image-preview">
                                            <img src="<?= UPLOADS_URL ?>/images/<?= e($category['background_image']) ?>" 
                                                 alt="Current background image" 
                                                 style="max-width: 400px; max-height: 200px; border-radius: 8px; object-fit: cover;">
                                            <br>
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm mt-2" 
                                                    onclick="deleteCategoryImage(<?= e($category['id']) ?>)">
                                                <i class="fas fa-trash"></i> Delete Image
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="file-preview mt-3"></div>
                            </div>
                            
                            <div class="form-actions mt-4">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                
                                <button type="submit" name="save_and_close" value="1" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save and Close
                                </button>
                                
                                <a href="categories.php" class="btn btn-danger">
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
        // Initialize select all functionality
        document.getElementById('selectAll')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.category-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Auto-generate slug from title
        document.getElementById('title')?.addEventListener('input', function() {
            const slugField = document.getElementById('slug');
            if (slugField && (!slugField.value || slugField.dataset.autoGenerated)) {
                slugField.value = AdminCMS.generateSlug(this.value);
                slugField.dataset.autoGenerated = 'true';
            }
        });
        
        // Mark slug as manually edited
        document.getElementById('slug')?.addEventListener('input', function() {
            this.dataset.autoGenerated = 'false';
        });
        
        // Delete category image function
        function deleteCategoryImage(categoryId) {
            if (!confirm('Are you sure you want to delete this background image?')) {
                return;
            }
            
            AdminCMS.ajax('ajax/delete_category_image.php', {
                method: 'POST',
                body: JSON.stringify({
                    category_id: categoryId
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
    </script>
    
    <style>
        .badge {
            background: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .table-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
        }
        
        .bulk-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .form-actions {
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
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
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .table {
                min-width: 600px;
            }
        }
    </style>
</body>
</html>

<?php
/**
 * Category Management Functions
 */

function createCategory($data, $files) {
    $db = DB::getInstance();
    
    // Validate input
    $title = InputValidator::validateText($data['title'], 50, true);
    $slug = !empty($data['slug']) ? InputValidator::validateSlug($data['slug']) : InputValidator::generateSlug($title);
    $description = InputValidator::validateHtmlContent($data['description'] ?? '');
    $status = in_array($data['status'] ?? 'active', ['active', 'inactive']) ? $data['status'] : 'active';
    
    // Check if slug is unique
    $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetch()) {
        throw new Exception("A category with this slug already exists");
    }
    
    // Handle background image upload
    $imageName = null;
    if (!empty($files['background_image']['name'])) {
        $imageName = uploadImage($files['background_image'], UPLOAD_IMAGES_PATH, CATEGORY_BACKGROUND_WIDTH);
        generateThumbnail(UPLOAD_IMAGES_PATH . '/' . $imageName, UPLOAD_THUMBNAILS_PATH);
    }
    
    // Insert category
    $stmt = $db->prepare("
        INSERT INTO categories (title, slug, description, background_image, status, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$title, $slug, $description, $imageName, $status]);
    
    return $db->lastInsertId();
}

function updateCategory($categoryId, $data, $files) {
    $db = DB::getInstance();
    $categoryId = InputValidator::validateInteger($categoryId, 1, null, true);
    
    // Get current category
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $currentCategory = $stmt->fetch();
    
    if (!$currentCategory) {
        throw new Exception("Category not found");
    }
    
    // Validate input
    $title = InputValidator::validateText($data['title'], 50, true);
    $slug = !empty($data['slug']) ? InputValidator::validateSlug($data['slug']) : InputValidator::generateSlug($title);
    $description = InputValidator::validateHtmlContent($data['description'] ?? '');
    $status = in_array($data['status'] ?? 'active', ['active', 'inactive']) ? $data['status'] : 'active';
    
    // Check if slug is unique (excluding current category)
    $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
    $stmt->execute([$slug, $categoryId]);
    if ($stmt->fetch()) {
        throw new Exception("A category with this slug already exists");
    }
    
    // Handle background image upload
    $imageName = $currentCategory['background_image'];
    if (!empty($files['background_image']['name'])) {
        // Delete old image if exists
        if ($imageName) {
            @unlink(UPLOAD_IMAGES_PATH . '/' . $imageName);
            @unlink(UPLOAD_THUMBNAILS_PATH . '/' . pathinfo($imageName, PATHINFO_FILENAME) . '_thumb.' . pathinfo($imageName, PATHINFO_EXTENSION));
        }
        
        $imageName = uploadImage($files['background_image'], UPLOAD_IMAGES_PATH, CATEGORY_BACKGROUND_WIDTH);
        generateThumbnail(UPLOAD_IMAGES_PATH . '/' . $imageName, UPLOAD_THUMBNAILS_PATH);
    }
    
    // Update category
    $stmt = $db->prepare("
        UPDATE categories 
        SET title = ?, slug = ?, description = ?, background_image = ?, status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$title, $slug, $description, $imageName, $status, $categoryId]);
    
    // Redirect based on save action
    if (isset($data['save_and_close'])) {
        redirect('categories.php', 'Category updated successfully!', 'success');
    }
}

function deleteCategory($categoryId) {
    $db = DB::getInstance();
    $categoryId = InputValidator::validateInteger($categoryId, 1, null, true);
    
    // Get category to delete image and check for products
    $stmt = $db->prepare("
        SELECT c.background_image, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id 
        WHERE c.id = ? 
        GROUP BY c.id
    ");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();
    
    if (!$category) {
        throw new Exception("Category not found");
    }
    
    if ($category['product_count'] > 0) {
        throw new Exception("Cannot delete category that contains products. Please delete or move the products first.");
    }
    
    // Delete background image if exists
    if ($category['background_image']) {
        @unlink(UPLOAD_IMAGES_PATH . '/' . $category['background_image']);
        @unlink(UPLOAD_THUMBNAILS_PATH . '/' . pathinfo($category['background_image'], PATHINFO_FILENAME) . '_thumb.' . pathinfo($category['background_image'], PATHINFO_EXTENSION));
    }
    
    // Delete category
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Category not found");
    }
}

function bulkDeleteCategories($categoryIds) {
    foreach ($categoryIds as $categoryId) {
        $categoryId = InputValidator::validateInteger($categoryId, 1);
        if ($categoryId) {
            try {
                deleteCategory($categoryId);
            } catch (Exception $e) {
                // Log error but continue with other deletions
                ErrorHandler::logError("Failed to delete category {$categoryId}: " . $e->getMessage());
            }
        }
    }
}

function getCategory($categoryId) {
    $db = DB::getInstance();
    $categoryId = InputValidator::validateInteger($categoryId, 1, null, true);
    
    $stmt = $db->prepare("
        SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id 
        WHERE c.id = ? 
        GROUP BY c.id
    ");
    $stmt->execute([$categoryId]);
    
    return $stmt->fetch();
}

function getCategories($page = 1, $search = '', $statusFilter = '', $sortBy = 'created_at', $sortOrder = 'DESC') {
    $db = DB::getInstance();
    
    $page = max(1, intval($page));
    $limit = DEFAULT_ITEMS_PER_PAGE;
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause
    $where = ['1=1'];
    $params = [];
    
    if (!empty($search)) {
        $where[] = '(c.title LIKE ? OR c.description LIKE ?)';
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    if (!empty($statusFilter)) {
        $where[] = 'c.status = ?';
        $params[] = $statusFilter;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Validate sort parameters
    $allowedSort = ['created_at', 'title'];
    $sortBy = in_array($sortBy, $allowedSort) ? $sortBy : 'created_at';
    $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
    
    // Get total count
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM categories c 
        WHERE {$whereClause}
    ");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Get categories with product count
    $stmt = $db->prepare("
        SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id 
        WHERE {$whereClause} 
        GROUP BY c.id 
        ORDER BY c.{$sortBy} {$sortOrder} 
        LIMIT ? OFFSET ?
    ");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $categories = $stmt->fetchAll();
    
    return [
        'data' => $categories,
        'total' => $total,
        'current_page' => $page,
        'total_pages' => ceil($total / $limit),
        'per_page' => $limit
    ];
}
?>