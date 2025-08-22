<?php
// Custom HTML Module Fields
$content = '';
if ($action === 'edit' && isset($module['content'])) {
    $content = $module['content'];
}
?>

<div class="form-section">
    <h3>Custom HTML Content</h3>
    
    <div class="form-group">
        <label for="content">HTML Code *</label>
        <textarea id="content" 
                  name="content" 
                  class="form-control html-editor" 
                  rows="15"
                  required><?= e($content) ?></textarea>
        <small class="text-muted">
            Enter your custom HTML code. Be careful with the code you enter as it will be displayed directly on the website.
        </small>
    </div>
    
    <div class="html-preview">
        <h4>Live Preview</h4>
        <div class="preview-container">
            <div id="htmlPreview">
                <!-- Preview will be displayed here -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const htmlEditor = document.getElementById('content');
    const preview = document.getElementById('htmlPreview');
    
    function updatePreview() {
        if (htmlEditor && preview) {
            preview.innerHTML = htmlEditor.value;
        }
    }
    
    if (htmlEditor) {
        // Update preview on input
        htmlEditor.addEventListener('input', updatePreview);
        
        // Initial preview update
        updatePreview();
        
        // Add syntax highlighting (basic)
        htmlEditor.addEventListener('input', function() {
            this.style.fontFamily = 'Monaco, Consolas, "Courier New", monospace';
        });
    }
});
</script>

<style>
.html-editor {
    font-family: 'Monaco', 'Consolas', 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.4;
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
}

.html-editor:focus {
    outline: none;
    border-color: #007bff;
    background: white;
}

.html-preview {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.html-preview h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 15px;
}

.preview-container {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    background: white;
    min-height: 100px;
    max-height: 300px;
    overflow-y: auto;
}

#htmlPreview {
    font-family: inherit;
    font-size: 14px;
    line-height: 1.5;
}

/* Reset styles for preview */
#htmlPreview * {
    max-width: 100%;
}

#htmlPreview img {
    height: auto;
}
</style>