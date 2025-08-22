<?php
// Menu Module Fields
$menuItems = $module['menu_items'] ?? [];
?>

<div class="form-section">
    <h3>Navigation Menu Configuration</h3>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        This menu will automatically include a hamburger menu for mobile devices and be fixed to the top when scrolling.
    </div>
    
    <div class="form-group">
        <label>Menu Items</label>
        <p class="text-muted">Select categories to include in the navigation menu. Drag to reorder.</p>
        
        <div id="menuItemsContainer" class="menu-items-container">
            <?php if (!empty($menuItems)): ?>
                <?php foreach ($menuItems as $index => $item): ?>
                    <div class="menu-item" data-index="<?= $index ?>">
                        <div class="menu-item-drag">
                            <i class="fas fa-grip-vertical"></i>
                        </div>
                        <div class="menu-item-content">
                            <select name="menu_categories[<?= $index ?>]" class="form-control form-select">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= e($category['id']) ?>" 
                                            <?= $item['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                        <?= e($category['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="menu-item-actions">
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeMenuItem(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <button type="button" id="addMenuItem" class="btn btn-outline btn-sm mt-3">
            <i class="fas fa-plus"></i> Add Menu Item
        </button>
    </div>
    
    <div class="form-group">
        <label>Menu Preview</label>
        <div class="menu-preview">
            <div class="preview-menu" id="menuPreview">
                <!-- Menu preview will be generated here -->
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label>Mobile Menu Settings</label>
        <div class="form-grid">
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="enable_hamburger" name="enable_hamburger" class="form-check-input" checked>
                    <label for="enable_hamburger" class="form-check-label">
                        Enable Hamburger Menu (Mobile)
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="sticky_menu" name="sticky_menu" class="form-check-input" checked>
                    <label for="sticky_menu" class="form-check-label">
                        Sticky Menu (Fixed on Scroll)
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let menuItemIndex = <?= count($menuItems) ?>;

document.addEventListener('DOMContentLoaded', function() {
    updateMenuPreview();
    
    // Add new menu item
    document.getElementById('addMenuItem').addEventListener('click', function() {
        addMenuItem();
    });
    
    // Update preview when categories change
    document.addEventListener('change', function(e) {
        if (e.target.matches('select[name^="menu_categories"]')) {
            updateMenuPreview();
        }
    });
    
    // Initialize sortable for menu items
    initMenuSortable();
});

function addMenuItem() {
    const container = document.getElementById('menuItemsContainer');
    const menuItem = document.createElement('div');
    menuItem.className = 'menu-item';
    menuItem.dataset.index = menuItemIndex;
    
    menuItem.innerHTML = `
        <div class="menu-item-drag">
            <i class="fas fa-grip-vertical"></i>
        </div>
        <div class="menu-item-content">
            <select name="menu_categories[${menuItemIndex}]" class="form-control form-select">
                <option value="">Select Category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category['id']) ?>"><?= e($category['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="menu-item-actions">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeMenuItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    container.appendChild(menuItem);
    menuItemIndex++;
    
    // Add change listener to new select
    menuItem.querySelector('select').addEventListener('change', updateMenuPreview);
    
    updateMenuPreview();
}

function removeMenuItem(button) {
    const menuItem = button.closest('.menu-item');
    menuItem.remove();
    updateMenuPreview();
}

function updateMenuPreview() {
    const preview = document.getElementById('menuPreview');
    const selects = document.querySelectorAll('select[name^="menu_categories"]');
    
    preview.innerHTML = '';
    
    selects.forEach(select => {
        if (select.value) {
            const option = select.options[select.selectedIndex];
            const link = document.createElement('a');
            link.href = '#';
            link.className = 'preview-menu-item';
            link.textContent = option.text;
            preview.appendChild(link);
        }
    });
    
    if (preview.children.length === 0) {
        preview.innerHTML = '<span class="no-items">No menu items selected</span>';
    }
}

function initMenuSortable() {
    const container = document.getElementById('menuItemsContainer');
    let draggedItem = null;
    
    container.addEventListener('dragstart', function(e) {
        if (e.target.closest('.menu-item')) {
            draggedItem = e.target.closest('.menu-item');
            draggedItem.style.opacity = '0.5';
        }
    });
    
    container.addEventListener('dragend', function(e) {
        if (draggedItem) {
            draggedItem.style.opacity = '';
            draggedItem = null;
            updateMenuItemIndexes();
            updateMenuPreview();
        }
    });
    
    container.addEventListener('dragover', function(e) {
        e.preventDefault();
    });
    
    container.addEventListener('drop', function(e) {
        e.preventDefault();
        
        const dropTarget = e.target.closest('.menu-item');
        if (draggedItem && dropTarget && draggedItem !== dropTarget) {
            const rect = dropTarget.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            
            if (e.clientY < midY) {
                container.insertBefore(draggedItem, dropTarget);
            } else {
                container.insertBefore(draggedItem, dropTarget.nextSibling);
            }
        }
    });
    
    // Make existing items draggable
    container.querySelectorAll('.menu-item').forEach(item => {
        item.draggable = true;
    });
}

function updateMenuItemIndexes() {
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach((item, index) => {
        const select = item.querySelector('select');
        select.name = `menu_categories[${index}]`;
        item.dataset.index = index;
    });
}
</script>

<style>
.menu-items-container {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: white;
    min-height: 100px;
    padding: 10px;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    margin-bottom: 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.menu-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.menu-item.dragging {
    opacity: 0.5;
}

.menu-item-drag {
    cursor: move;
    color: #6c757d;
    font-size: 14px;
}

.menu-item-content {
    flex: 1;
}

.menu-item-actions {
    display: flex;
    gap: 5px;
}

.menu-preview {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    background: white;
    margin-top: 10px;
}

.preview-menu {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
}

.preview-menu-item {
    color: #2c3e50;
    text-decoration: none;
    padding: 8px 15px;
    background: #e9ecef;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.preview-menu-item:hover {
    background: #007bff;
    color: white;
    text-decoration: none;
}

.no-items {
    color: #6c757d;
    font-style: italic;
}

.form-check {
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-check-input {
    margin: 0;
}

/* Mobile preview */
@media (max-width: 768px) {
    .preview-menu {
        position: relative;
    }
    
    .preview-menu::before {
        content: '☰ Menu';
        display: block;
        width: 100%;
        padding: 10px;
        background: #007bff;
        color: white;
        text-align: center;
        border-radius: 6px;
        margin-bottom: 10px;
        font-size: 14px;
    }
    
    .preview-menu-item {
        display: none;
    }
    
    .preview-menu:hover .preview-menu-item {
        display: block;
        width: 100%;
        margin-bottom: 5px;
    }
}

/* Alert styling */
.alert {
    background: #d1ecf1;
    color: #0c5460;
    padding: 12px 15px;
    border-radius: 8px;
    border-left: 4px solid #17a2b8;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert i {
    color: #17a2b8;
}
</style>