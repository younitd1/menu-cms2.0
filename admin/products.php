<?php
require_once '../config/config.php';
SecureSession::requireLogin();

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

// Handle actions
$action = $_GET['action'] ?? 'list';
$productId = $_GET['id'] ?? null;
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
                $productId = createProduct($_POST, $_FILES);
                redirect("products.php?action=edit&id={$productId}", 'Product created successfully!', 'success');
                break;
                
            case 'edit':
                updateProduct($productId, $_POST, $_FILES);
                redirect("products.php?action=edit&id={$productId}", 'Product updated successfully!', 'success');
                break;
                
            case 'delete':
                deleteProduct($productId);
                redirect('products.php', 'Product deleted successfully!', 'success');
                break;
                
            case 'bulk_delete':
                $selectedIds = $_POST['selected_products'] ?? [];
                if (!empty($selectedIds)) {
                    bulkDeleteProducts($selectedIds);
                    redirect('products.php', count($selectedIds) . ' products deleted successfully!', 'success');
                } else {
                    $message = 'Please select products to delete.';
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
if ($action === 'delete' && $productId && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        deleteProduct($productId);
        redirect('products.php', 'Product deleted successfully!', 'success');
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get data based on action
$product = null;
$products = [];
$categories = [];

if ($action === 'edit' && $productId) {
    $product = getProduct($productId);
    if (!$product) {
        redirect('products.php', 'Product not found.', 'error');
    }
}

if ($action === 'list') {
    $page = $_GET['page'] ?? 1;
    $search = $_GET['search'] ?? '';
    $categoryFilter = $_GET['category'] ?? '';
    $statusFilter = $_GET['status'] ?? '';
    $sortBy = $_GET['sort'] ?? 'created_at';
    $sortOrder = $_GET['order'] ?? 'DESC';
    $viewMode = $_GET['view'] ?? 'list';
    
    $products = getProducts($page, $search, $categoryFilter, $statusFilter, $sortBy, $sortOrder);
}

if ($action === 'add' || $action === 'edit') {
    $categories = getCategories();
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
    <title>Products - CMS Admin</title>
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
                        <h1 class="page-title">Products</h1>
                        <div class="page-actions">
                            <a href="products.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New Product
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
                                <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?= e($search) ?>">
                            </div>
                            
                            <select name="category" class="form-control form-select">
                                <option value="">All Categories</option>
                                <?php foreach (getCategories() as $cat): ?>
                                    <option value="<?= e($cat['id']) ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                                        <?= e($cat['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select name="status" class="form-control form-select">
                                <option value="">All Status</option>
                                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                            
                            <select name="sort" class="form-control form-select">
                                <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Date Created</option>
                                <option value="title" <?= $sortBy === 'title' ? 'selected' : '' ?>>Title</option>
                                <option value="price" <?= $sortBy === 'price' ? 'selected' : '' ?>>Price</option>
                            </select>
                            
                            <select name="view" class="form-control form-select">
                                <option value="list" <?= $viewMode === 'list' ? 'selected' : '' ?>>List View</option>
                                <option value="grid" <?= $viewMode === 'grid' ? 'selected' : '' ?>>Grid View</option>
                            </select>
                            
                            <button type="submit" class="btn btn-secondary">Filter</button>
                        </form>
                    </div>
                    
                    <!-- Products List/Grid -->
                    <div class="table-container">
                        <form method="POST" action="products.php?action=bulk_delete" id="bulkForm">
                            <input type="hidden" name="csrf_token" value="<?= e(CSRFProtection::generateToken()) ?>">
                            
                            <div class="table-header">
                                <div class="bulk-actions">
                                    <input type="checkbox" id="selectAll">
                                    <label for="selectAll">Select All</label>
                                    <button type="submit" class="btn btn-danger btn-sm" data-confirm="Are you sure you want to delete selected products?">
                                        <i class="fas fa-trash"></i> Delete Selected
                                    </button>
                                </div>
                            </div>
                            
                            <?php if ($viewMode === 'grid'): ?>
                                <div class="products-grid">
                                    <?php foreach ($products['data'] as $product): ?>
                                        <div class="product-grid-item">
                                            <input type="checkbox" name="selected_products[]" value="<?= e($product['id']) ?>" class="product-checkbox">
                                            
                                            <div class="product-image">
                                                <?php if ($product['image']): ?>
                                                    <img src="<?= UPLOADS_URL ?>/images/<?= e($product['image']) ?>" alt="<?= e($product['title']) ?>">
                                                <?php else: ?>
                                                    <div class="no-image">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                        </form>
                        
                        <!-- Pagination -->
                        <?php if ($products['total_pages'] > 1): ?>
                            <div class="pagination">
                                <?php for ($i = 1; $i <= $products['total_pages']; $i++): ?>
                                    <?php if ($i == $products['current_page']): ?>
                                        <span class="current"><?= $i ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categoryFilter) ?>&status=<?= urlencode($statusFilter) ?>&sort=<?= urlencode($sortBy) ?>&view=<?= urlencode($viewMode) ?>"><?= $i ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                
                <?php elseif ($action === 'add' || $action === 'edit'): ?>
                    <div class="page-header">
                        <h1 class="page-title"><?= $action === 'add' ? 'Add New Product' : 'Edit Product' ?></h1>
                        <div class="page-actions">
                            <a href="products.php" class="btn btn-outline">
                                <i class="fas fa-arrow-left"></i> Back to Products
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
                                    <label for="title">Product Title *</label>
                                    <input type="text" 
                                           id="title" 
                                           name="title" 
                                           class="form-control" 
                                           value="<?= e($product['title'] ?? '') ?>"
                                           data-slug-from="#slug"
                                           required 
                                           maxlength="50">
                                </div>
                                
                                <div class="form-group">
                                    <label for="slug">Slug (URL)</label>
                                    <input type="text" 
                                           id="slug" 
                                           name="slug" 
                                           class="form-control" 
                                           value="<?= e($product['slug'] ?? '') ?>"
                                           maxlength="255">
                                    <small class="text-muted">Auto-generated from title if left empty</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="category_id">Category *</label>
                                    <select id="category_id" name="category_id" class="form-control form-select" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= e($category['id']) ?>" 
                                                    <?= ($product['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                                <?= e($category['title']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="price">Price</label>
                                    <input type="number" 
                                           id="price" 
                                           name="price" 
                                           class="form-control price-input" 
                                           value="<?= e($product['price'] ?? '0.00') ?>"
                                           step="0.01" 
                                           min="0">
                                </div>
                                
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select id="status" name="status" class="form-control form-select">
                                        <option value="active" <?= ($product['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= ($product['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="short_description">Short Description</label>
                                <textarea id="short_description" 
                                          name="short_description" 
                                          class="form-control" 
                                          rows="3"
                                          maxlength="500"><?= e($product['short_description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="full_description">Full Description</label>
                                <textarea id="full_description" 
                                          name="full_description" 
                                          class="form-control rich-text-editor" 
                                          rows="8"><?= e($product['full_description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Product Image</label>
                                <div class="file-upload-area" 
                                     data-max-size="307200" 
                                     data-allowed-types="image/jpeg">
                                    <input type="file" 
                                           name="image" 
                                           accept="image/jpeg"
                                           style="display: none;">
                                    <div class="upload-text">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Click to upload or drag and drop</p>
                                        <small>JPG only, max 300KB</small>
                                    </div>
                                </div>
                                
                                <?php if ($action === 'edit' && !empty($product['image'])): ?>
                                    <div class="current-image mt-3">
                                        <label>Current Image:</label>
                                        <div class="image-preview">
                                            <img src="<?= UPLOADS_URL ?>/images/<?= e($product['image']) ?>" 
                                                 alt="Current product image" 
                                                 style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm mt-2" 
                                                    onclick="deleteProductImage(<?= e($product['id']) ?>)">
                                                <i class="fas fa-trash"></i> Delete Image
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="file-preview mt-3"></div>
                            </div>
                            
                            <?php if ($action === 'edit' && !empty($product['image'])): ?>
                                <div class="form-group">
                                    <label for="image_alignment">Image Alignment</label>
                                    <select id="image_alignment" name="image_alignment" class="form-control form-select">
                                        <option value="none" <?= ($product['image_alignment'] ?? 'none') === 'none' ? 'selected' : '' ?>>None</option>
                                        <option value="left" <?= ($product['image_alignment'] ?? '') === 'left' ? 'selected' : '' ?>>Left</option>
                                        <option value="right" <?= ($product['image_alignment'] ?? '') === 'right' ? 'selected' : '' ?>>Right</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-actions mt-4">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                
                                <button type="submit" name="save_and_close" value="1" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save and Close
                                </button>
                                
                                <a href="products.php" class="btn btn-danger">
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
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Auto-generate slug from title
        document.getElementById('title')?.addEventListener('input', function() {
            const slugField = document.getElementById('slug');
            if (slugField && !slugField.value) {
                slugField.value = AdminCMS.generateSlug(this.value);
            }
        });
        
        // Delete product image function
        function deleteProductImage(productId) {
            if (!confirm('Are you sure you want to delete this image?')) {
                return;
            }
            
            AdminCMS.ajax('ajax/delete_product_image.php', {
                method: 'POST',
                body: JSON.stringify({
                    product_id: productId
                })
            }).then(response => {
                if (response.success) {
                    document.querySelector('.current-image').remove();
                    AdminCMS.showAlert('Image deleted successfully!', 'success');
                } else {
                    AdminCMS.showAlert('Failed to delete image', 'error');
                }
            });
        }
    </script>
    
    <style>
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .product-grid-item {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            position: relative;
        }
        
        .product-grid-item:hover {
            transform: translateY(-2px);
        }
        
        .product-grid-item .product-checkbox {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2;
        }
        
        .product-image {
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-image {
            color: #6c757d;
            font-size: 3rem;
        }
        
        .product-info {
            padding: 15px;
        }
        
        .product-title {
            margin: 0 0 10px 0;
            font-size: 1.1rem;
        }
        
        .product-title a {
            color: #2c3e50;
            text-decoration: none;
        }
        
        .product-title a:hover {
            color: #007bff;
        }
        
        .product-price {
            font-size: 1.2rem;
            font-weight: 600;
            color: #28a745;
            margin: 5px 0;
        }
        
        .product-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .product-actions {
            display: flex;
            gap: 8px;
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
        
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr;
                padding: 10px;
            }
            
            .form-grid {
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
 * Product Management Functions
 */

function createProduct($data, $files) {
    $db = DB::getInstance();
    
    // Validate input
    $title = InputValidator::validateText($data['title'], 50, true);
    $slug = !empty($data['slug']) ? InputValidator::validateSlug($data['slug']) : InputValidator::generateSlug($title);
    $categoryId = InputValidator::validateInteger($data['category_id'], 1, null, true);
    $shortDescription = InputValidator::validateText($data['short_description'] ?? '', 500, false);
    $fullDescription = InputValidator::validateHtmlContent($data['full_description'] ?? '');
    $price = InputValidator::validateInteger($data['price'] ?? 0, 0) / 100; // Convert cents to dollars
    $status = in_array($data['status'] ?? 'active', ['active', 'inactive']) ? $data['status'] : 'active';
    
    // Check if slug is unique
    $stmt = $db->prepare("SELECT id FROM products WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetch()) {
        throw new Exception("A product with this slug already exists");
    }
    
    // Handle image upload
    $imageName = null;
    if (!empty($files['image']['name'])) {
        $imageName = uploadImage($files['image'], UPLOAD_IMAGES_PATH, PRODUCT_IMAGE_MAX_WIDTH);
        generateThumbnail(UPLOAD_IMAGES_PATH . '/' . $imageName, UPLOAD_THUMBNAILS_PATH);
    }
    
    // Insert product
    $stmt = $db->prepare("
        INSERT INTO products (title, slug, category_id, short_description, full_description, price, image, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$title, $slug, $categoryId, $shortDescription, $fullDescription, $price, $imageName, $status]);
    
    return $db->lastInsertId();
}

function updateProduct($productId, $data, $files) {
    $db = DB::getInstance();
    $productId = InputValidator::validateInteger($productId, 1, null, true);
    
    // Get current product
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $currentProduct = $stmt->fetch();
    
    if (!$currentProduct) {
        throw new Exception("Product not found");
    }
    
    // Validate input
    $title = InputValidator::validateText($data['title'], 50, true);
    $slug = !empty($data['slug']) ? InputValidator::validateSlug($data['slug']) : InputValidator::generateSlug($title);
    $categoryId = InputValidator::validateInteger($data['category_id'], 1, null, true);
    $shortDescription = InputValidator::validateText($data['short_description'] ?? '', 500, false);
    $fullDescription = InputValidator::validateHtmlContent($data['full_description'] ?? '');
    $price = floatval($data['price'] ?? 0);
    $status = in_array($data['status'] ?? 'active', ['active', 'inactive']) ? $data['status'] : 'active';
    $imageAlignment = in_array($data['image_alignment'] ?? 'none', ['none', 'left', 'right']) ? $data['image_alignment'] : 'none';
    
    // Check if slug is unique (excluding current product)
    $stmt = $db->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
    $stmt->execute([$slug, $productId]);
    if ($stmt->fetch()) {
        throw new Exception("A product with this slug already exists");
    }
    
    // Handle image upload
    $imageName = $currentProduct['image'];
    if (!empty($files['image']['name'])) {
        // Delete old image if exists
        if ($imageName) {
            @unlink(UPLOAD_IMAGES_PATH . '/' . $imageName);
            @unlink(UPLOAD_THUMBNAILS_PATH . '/' . pathinfo($imageName, PATHINFO_FILENAME) . '_thumb.' . pathinfo($imageName, PATHINFO_EXTENSION));
        }
        
        $imageName = uploadImage($files['image'], UPLOAD_IMAGES_PATH, PRODUCT_IMAGE_MAX_WIDTH);
        generateThumbnail(UPLOAD_IMAGES_PATH . '/' . $imageName, UPLOAD_THUMBNAILS_PATH);
    }
    
    // Update product
    $stmt = $db->prepare("
        UPDATE products 
        SET title = ?, slug = ?, category_id = ?, short_description = ?, full_description = ?, 
            price = ?, image = ?, image_alignment = ?, status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$title, $slug, $categoryId, $shortDescription, $fullDescription, $price, $imageName, $imageAlignment, $status, $productId]);
    
    // Redirect based on save action
    if (isset($data['save_and_close'])) {
        redirect('products.php', 'Product updated successfully!', 'success');
    }
}

function deleteProduct($productId) {
    $db = DB::getInstance();
    $productId = InputValidator::validateInteger($productId, 1, null, true);
    
    // Get product to delete image
    $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if ($product && $product['image']) {
        @unlink(UPLOAD_IMAGES_PATH . '/' . $product['image']);
        @unlink(UPLOAD_THUMBNAILS_PATH . '/' . pathinfo($product['image'], PATHINFO_FILENAME) . '_thumb.' . pathinfo($product['image'], PATHINFO_EXTENSION));
    }
    
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Product not found");
    }
}

function bulkDeleteProducts($productIds) {
    $db = DB::getInstance();
    
    foreach ($productIds as $productId) {
        $productId = InputValidator::validateInteger($productId, 1);
        if ($productId) {
            deleteProduct($productId);
        }
    }
}

function getProduct($productId) {
    $db = DB::getInstance();
    $productId = InputValidator::validateInteger($productId, 1, null, true);
    
    $stmt = $db->prepare("
        SELECT p.*, c.title as category_title 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$productId]);
    
    return $stmt->fetch();
}

function getProducts($page = 1, $search = '', $categoryFilter = '', $statusFilter = '', $sortBy = 'created_at', $sortOrder = 'DESC') {
    $db = DB::getInstance();
    
    $page = max(1, intval($page));
    $limit = DEFAULT_ITEMS_PER_PAGE;
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause
    $where = ['1=1'];
    $params = [];
    
    if (!empty($search)) {
        $where[] = '(p.title LIKE ? OR p.short_description LIKE ?)';
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    if (!empty($categoryFilter)) {
        $where[] = 'p.category_id = ?';
        $params[] = $categoryFilter;
    }
    
    if (!empty($statusFilter)) {
        $where[] = 'p.status = ?';
        $params[] = $statusFilter;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Validate sort parameters
    $allowedSort = ['created_at', 'title', 'price'];
    $sortBy = in_array($sortBy, $allowedSort) ? $sortBy : 'created_at';
    $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
    
    // Get total count
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE {$whereClause}
    ");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Get products
    $stmt = $db->prepare("
        SELECT p.*, c.title as category_title 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE {$whereClause} 
        ORDER BY p.{$sortBy} {$sortOrder} 
        LIMIT ? OFFSET ?
    ");
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    return [
        'data' => $products,
        'total' => $total,
        'current_page' => $page,
        'total_pages' => ceil($total / $limit),
        'per_page' => $limit
    ];
}

function getCategories() {
    $db = DB::getInstance();
    $stmt = $db->prepare("SELECT id, title FROM categories WHERE status = 'active' ORDER BY title");
    $stmt->execute();
    return $stmt->fetchAll();
}
?>
                                            </div>
                                            
                                            <div class="product-info">
                                                <h3 class="product-title">
                                                    <a href="products.php?action=edit&id=<?= e($product['id']) ?>">
                                                        <?= e($product['title']) ?>
                                                    </a>
                                                </h3>
                                                <p class="product-price">$<?= number_format($product['price'], 2) ?></p>
                                                <p class="product-description"><?= e(substr($product['short_description'], 0, 100)) ?>...</p>
                                                
                                                <div class="product-actions">
                                                    <a href="products.php?action=edit&id=<?= e($product['id']) ?>" class="btn btn-sm btn-secondary">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="products.php?action=delete&id=<?= e($product['id']) ?>" class="btn btn-sm btn-danger" data-confirm="Are you sure you want to delete this product?">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th width="50">
                                                <input type="checkbox" id="selectAll">
                                            </th>
                                            <th width="80">Image</th>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th width="100">Status</th>
                                            <th width="120">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products['data'] as $product): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="selected_products[]" value="<?= e($product['id']) ?>" class="product-checkbox">
                                                </td>
                                                <td>
                                                    <?php if ($product['image']): ?>
                                                        <img src="<?= UPLOADS_URL ?>/images/<?= e($product['image']) ?>" 
                                                             alt="<?= e($product['title']) ?>" 
                                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                                                    <?php else: ?>
                                                        <div style="width: 60px; height: 60px; background: #f8f9fa; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?= e($product['title']) ?></strong><br>
                                                    <small class="text-muted"><?= e(substr($product['short_description'], 0, 50)) ?>...</small>
                                                </td>
                                                <td><?= e($product['category_title'] ?? 'No Category') ?></td>
                                                <td>$<?= number_format($product['price'], 2) ?></td>
                                                <td>
                                                    <span class="status-badge status-<?= e($product['status']) ?>">
                                                        <?= ucfirst($product['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="products.php?action=edit&id=<?= e($product['id']) ?>" class="btn btn-sm btn-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="products.php?action=delete&id=<?= e($product['id']) ?>" class="btn btn-sm btn-danger" data-confirm="Are you sure you want to delete this product?">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>