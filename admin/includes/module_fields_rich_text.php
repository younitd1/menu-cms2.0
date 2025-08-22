<?php
// Rich Text Module Fields
$content = '';
if ($action === 'edit' && isset($module['content'])) {
    $content = $module['content'];
}
?>

<div class="form-section">
    <h3>Rich Text Content</h3>
    
    <div class="form-group">
        <label for="content">Content *</label>
        <textarea id="content" 
                  name="content" 
                  class="form-control rich-text-editor" 
                  rows="12"
                  required><?= e($content) ?></textarea>
        <small class="text-muted">
            Use the toolbar to format your text. You can add headings, lists, links, and basic formatting.
        </small>
    </div>
</div>

<style>
.rich-text-toolbar {
    border: 2px solid #dee2e6;
    border-bottom: none;
    border-radius: 8px 8px 0 0;
    padding: 10px;
    background: #f8f9fa;
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.rich-text-toolbar button {
    border: 1px solid #dee2e6;
    background: white;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.3s ease;
}

.rich-text-toolbar button:hover {
    background: #e9ecef;
}

.rich-text-content {
    border: 2px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 8px 8px;
    padding: 15px;
    min-height: 200px;
    background: white;
    font-family: inherit;
    font-size: 14px;
    line-height: 1.5;
}

.rich-text-content:focus {
    outline: none;
    border-color: #007bff;
}
</style>