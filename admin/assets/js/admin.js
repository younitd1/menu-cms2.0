/**
 * Main Admin JavaScript Functions
 * Handles interactions, AJAX requests, and UI enhancements
 */

// Global admin object
window.AdminCMS = {
    init: function() {
        this.bindEvents();
        this.initComponents();
        this.setupAjax();
    },
    
    bindEvents: function() {
        // Confirm delete actions
        document.addEventListener('click', function(e) {
            if (e.target.matches('.btn-danger, .delete-btn') || e.target.closest('.btn-danger, .delete-btn')) {
                const btn = e.target.matches('.btn-danger, .delete-btn') ? e.target : e.target.closest('.btn-danger, .delete-btn');
                const confirmText = btn.dataset.confirm || 'Are you sure you want to delete this item?';
                
                if (!confirm(confirmText)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
        
        // Auto-generate slugs
        document.addEventListener('input', function(e) {
            if (e.target.matches('[data-slug-from]')) {
                const slugField = document.querySelector(e.target.dataset.slugFrom);
                if (slugField) {
                    slugField.value = AdminCMS.generateSlug(e.target.value);
                }
            }
        });
        
        // Form submission loading states
        document.addEventListener('submit', function(e) {
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            
            if (submitBtn && !form.dataset.skipLoading) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
                
                // Re-enable after 10 seconds as fallback
                setTimeout(() => {
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                }, 10000);
            }
        });
        
        // Auto-hide alerts
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    },
    
    initComponents: function() {
        // Initialize tooltips
        this.initTooltips();
        
        // Initialize file uploads
        this.initFileUploads();
        
        // Initialize rich text editors
        this.initRichTextEditors();
        
        // Initialize sortable lists
        this.initSortables();
    },
    
    setupAjax: function() {
        // Set default AJAX headers
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrfToken) {
            // Set default headers for fetch requests
            window.defaultHeaders = {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            };
        }
    },
    
    // Utility Functions
    generateSlug: function(text) {
        return text
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    },
    
    formatBytes: function(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    },
    
    showAlert: function(message, type = 'info') {
        const alertHtml = `
            <div class="alert alert-${type}" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                ${message}
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        // Auto remove after 5 seconds
        const alert = document.body.lastElementChild;
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    },
    
    // AJAX Helper Functions
    ajax: function(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: window.defaultHeaders || {},
            credentials: 'same-origin'
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        
        return fetch(url, finalOptions)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                this.showAlert('An error occurred. Please try again.', 'error');
                throw error;
            });
    },
    
    // Component Initializers
    initTooltips: function() {
        document.querySelectorAll('[title]').forEach(element => {
            element.addEventListener('mouseenter', function() {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = this.title;
                tooltip.style.cssText = `
                    position: absolute;
                    background: #333;
                    color: white;
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    z-index: 10000;
                    pointer-events: none;
                `;
                
                document.body.appendChild(tooltip);
                
                const updatePosition = (e) => {
                    tooltip.style.left = e.pageX + 10 + 'px';
                    tooltip.style.top = e.pageY - 30 + 'px';
                };
                
                this.addEventListener('mousemove', updatePosition);
                this.addEventListener('mouseleave', function() {
                    tooltip.remove();
                    this.removeEventListener('mousemove', updatePosition);
                }, { once: true });
                
                updatePosition({ pageX: 0, pageY: 0 });
            });
        });
    },
    
    initFileUploads: function() {
        document.querySelectorAll('.file-upload-area').forEach(area => {
            const input = area.querySelector('input[type="file"]');
            const preview = area.querySelector('.file-preview');
            
            if (input) {
                // Drag and drop
                area.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('dragover');
                });
                
                area.addEventListener('dragleave', function() {
                    this.classList.remove('dragover');
                });
                
                area.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('dragover');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        input.files = files;
                        AdminCMS.handleFileSelect(input, preview);
                    }
                });
                
                // Click to upload
                area.addEventListener('click', function() {
                    input.click();
                });
                
                input.addEventListener('change', function() {
                    AdminCMS.handleFileSelect(this, preview);
                });
            }
        });
    },
    
    handleFileSelect: function(input, preview) {
        const file = input.files[0];
        if (!file) return;
        
        // Validate file
        const maxSize = parseInt(input.dataset.maxSize) || 307200; // 300KB default
        const allowedTypes = input.dataset.allowedTypes?.split(',') || ['image/jpeg'];
        
        if (file.size > maxSize) {
            this.showAlert(`File size exceeds maximum of ${this.formatBytes(maxSize)}`, 'error');
            input.value = '';
            return;
        }
        
        if (!allowedTypes.includes(file.type)) {
            this.showAlert('Invalid file type. Only JPG images are allowed.', 'error');
            input.value = '';
            return;
        }
        
        // Show preview for images
        if (file.type.startsWith('image/') && preview) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px;">`;
            };
            reader.readAsDataURL(file);
        }
        
        // Update upload area text
        const uploadText = input.closest('.file-upload-area')?.querySelector('.upload-text');
        if (uploadText) {
            uploadText.textContent = `Selected: ${file.name}`;
        }
    },
    
    initRichTextEditors: function() {
        document.querySelectorAll('.rich-text-editor').forEach(textarea => {
            // Simple rich text editor implementation
            const toolbar = document.createElement('div');
            toolbar.className = 'rich-text-toolbar';
            toolbar.innerHTML = `
                <button type="button" data-command="bold"><strong>B</strong></button>
                <button type="button" data-command="italic"><em>I</em></button>
                <button type="button" data-command="underline"><u>U</u></button>
                <button type="button" data-command="insertUnorderedList">• List</button>
                <button type="button" data-command="insertOrderedList">1. List</button>
                <button type="button" data-command="createLink">🔗 Link</button>
            `;
            
            const editor = document.createElement('div');
            editor.className = 'rich-text-content';
            editor.contentEditable = true;
            editor.innerHTML = textarea.value;
            editor.style.cssText = `
                border: 2px solid #dee2e6;
                border-top: none;
                border-radius: 0 0 8px 8px;
                padding: 15px;
                min-height: 150px;
                background: white;
            `;
            
            toolbar.style.cssText = `
                border: 2px solid #dee2e6;
                border-bottom: none;
                border-radius: 8px 8px 0 0;
                padding: 10px;
                background: #f8f9fa;
                display: flex;
                gap: 5px;
            `;
            
            // Style toolbar buttons
            toolbar.querySelectorAll('button').forEach(btn => {
                btn.style.cssText = `
                    border: 1px solid #dee2e6;
                    background: white;
                    padding: 5px 10px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 12px;
                `;
                
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const command = this.dataset.command;
                    
                    if (command === 'createLink') {
                        const url = prompt('Enter URL:');
                        if (url) {
                            document.execCommand(command, false, url);
                        }
                    } else {
                        document.execCommand(command, false, null);
                    }
                    
                    editor.focus();
                });
            });
            
            // Update textarea value when editor changes
            editor.addEventListener('input', function() {
                textarea.value = this.innerHTML;
            });
            
            // Hide original textarea and insert editor
            textarea.style.display = 'none';
            textarea.parentNode.insertBefore(toolbar, textarea);
            textarea.parentNode.insertBefore(editor, textarea);
        });
    },
    
    initSortables: function() {
        document.querySelectorAll('.sortable-list').forEach(list => {
            let draggedItem = null;
            
            list.querySelectorAll('.sortable-item').forEach(item => {
                item.draggable = true;
                
                item.addEventListener('dragstart', function(e) {
                    draggedItem = this;
                    this.style.opacity = '0.5';
                });
                
                item.addEventListener('dragend', function() {
                    this.style.opacity = '';
                    draggedItem = null;
                });
                
                item.addEventListener('dragover', function(e) {
                    e.preventDefault();
                });
                
                item.addEventListener('drop', function(e) {
                    e.preventDefault();
                    
                    if (draggedItem && draggedItem !== this) {
                        const rect = this.getBoundingClientRect();
                        const midY = rect.top + rect.height / 2;
                        
                        if (e.clientY < midY) {
                            list.insertBefore(draggedItem, this);
                        } else {
                            list.insertBefore(draggedItem, this.nextSibling);
                        }
                        
                        // Update order numbers
                        AdminCMS.updateSortOrder(list);
                    }
                });
            });
        });
    },
    
    updateSortOrder: function(list) {
        const items = list.querySelectorAll('.sortable-item');
        items.forEach((item, index) => {
            const orderInput = item.querySelector('input[name*="order"]');
            if (orderInput) {
                orderInput.value = index + 1;
            }
        });
        
        // Trigger change event to save order
        const updateUrl = list.dataset.updateUrl;
        if (updateUrl) {
            const formData = new FormData();
            items.forEach((item, index) => {
                const itemId = item.dataset.itemId;
                if (itemId) {
                    formData.append(`order[${itemId}]`, index + 1);
                }
            });
            
            this.ajax(updateUrl, {
                method: 'POST',
                body: formData,
                headers: {} // FormData sets its own content-type
            }).then(() => {
                this.showAlert('Order updated successfully!', 'success');
            });
        }
    },
    
    // Module-specific functions
    toggleModuleStatus: function(moduleId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        
        this.ajax('modules.php?action=toggle_status', {
            method: 'POST',
            body: JSON.stringify({
                module_id: moduleId,
                status: newStatus
            })
        }).then(response => {
            if (response.success) {
                location.reload(); // Refresh to show updated status
            } else {
                this.showAlert('Failed to update module status', 'error');
            }
        });
    },
    
    // Search and filter functions
    initSearch: function() {
        const searchInputs = document.querySelectorAll('.search-input');
        
        searchInputs.forEach(input => {
            let timeout;
            
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    AdminCMS.performSearch(this.value, this.dataset.target);
                }, 300);
            });
        });
    },
    
    performSearch: function(query, target) {
        const targetContainer = document.querySelector(target);
        if (!targetContainer) return;
        
        const items = targetContainer.querySelectorAll('.searchable-item');
        
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            const matches = text.includes(query.toLowerCase());
            item.style.display = matches ? '' : 'none';
        });
    },
    
    // Image management
    deleteImage: function(imageId, imageType = 'general') {
        if (!confirm('Are you sure you want to delete this image?')) {
            return;
        }
        
        this.ajax('ajax/delete_image.php', {
            method: 'POST',
            body: JSON.stringify({
                image_id: imageId,
                image_type: imageType
            })
        }).then(response => {
            if (response.success) {
                const imageElement = document.querySelector(`[data-image-id="${imageId}"]`);
                if (imageElement) {
                    imageElement.remove();
                }
                this.showAlert('Image deleted successfully!', 'success');
            } else {
                this.showAlert('Failed to delete image', 'error');
            }
        });
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    AdminCMS.init();
});

// Additional utility functions for specific admin features
window.AdminUtils = {
    // Validate form before submission
    validateForm: function(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('error');
                isValid = false;
            } else {
                field.classList.remove('error');
            }
        });
        
        return isValid;
    },
    
    // Format price input
    formatPrice: function(input) {
        let value = input.value.replace(/[^\d.]/g, '');
        let parts = value.split('.');
        
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }
        
        if (parts[1] && parts[1].length > 2) {
            value = parts[0] + '.' + parts[1].substring(0, 2);
        }
        
        input.value = value;
    },
    
    // Copy to clipboard
    copyToClipboard: function(text) {
        navigator.clipboard.writeText(text).then(() => {
            AdminCMS.showAlert('Copied to clipboard!', 'success');
        }).catch(() => {
            AdminCMS.showAlert('Failed to copy to clipboard', 'error');
        });
    },
    
    // Preview image before upload
    previewImage: function(input, previewElement) {
        const file = input.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewElement.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100%; height: auto; border-radius: 8px;">`;
            };
            reader.readAsDataURL(file);
        }
    }
};

// Global event listeners for utility functions
document.addEventListener('input', function(e) {
    // Auto-format price inputs
    if (e.target.matches('input[type="number"][step="0.01"], .price-input')) {
        AdminUtils.formatPrice(e.target);
    }
    
    // Character counter for textareas with maxlength
    if (e.target.matches('textarea[maxlength]')) {
        const maxLength = parseInt(e.target.getAttribute('maxlength'));
        const currentLength = e.target.value.length;
        
        let counter = e.target.parentNode.querySelector('.char-counter');
        if (!counter) {
            counter = document.createElement('div');
            counter.className = 'char-counter';
            counter.style.cssText = 'font-size: 12px; color: #6c757d; margin-top: 5px; text-align: right;';
            e.target.parentNode.appendChild(counter);
        }
        
        counter.textContent = `${currentLength}/${maxLength}`;
        
        if (currentLength > maxLength * 0.9) {
            counter.style.color = '#dc3545';
        } else {
            counter.style.color = '#6c757d';
        }
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+S to save forms
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        const form = document.querySelector('form');
        if (form) {
            form.submit();
        }
    }
    
    // Escape to close modals
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            openModal.classList.remove('show');
        }
    }
});