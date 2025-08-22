<?php
// Image Module Fields
$imageData = [];
if ($action === 'edit' && isset($module['content'])) {
    $imageData = json_decode($module['content'], true) ?: [];
}

$images = $module['images'] ?? [];
$slideshowEnabled = $imageData['slideshow_enabled'] ?? false;
$clickTracking = $imageData['click_tracking'] ?? false;
?>

<div class="form-section">
    <h3>Image Configuration</h3>
    
    <div class="form-group">
        <div class="form-check">
            <input type="checkbox" 
                   id="slideshow_enabled" 
                   name="slideshow_enabled" 
                   class="form-check-input"
                   <?= $slideshowEnabled ? 'checked' : '' ?>
                   onchange="toggleSlideshowMode()">
            <label for="slideshow_enabled" class="form-check-label">
                Enable Slideshow (up to 10 images)
            </label>
        </div>
    </div>
    
    <div class="form-group">
        <div class="form-check">
            <input type="checkbox" 
                   id="click_tracking" 
                   name="click_tracking" 
                   class="form-check-input"
                   <?= $clickTracking ? 'checked' : '' ?>>
            <label for="click_tracking" class="form-check-label">
                Enable Click Tracking (for banner analytics)
            </label>
        </div>
    </div>
    
    <!-- Single Image Mode -->
    <div id="singleImageMode" class="image-mode" <?= $slideshowEnabled ? 'style="display: none;"' : '' ?>>
        <div class="form-group">
            <label>Upload Image</label>
            <div class="file-upload-area" 
                 data-max-size="307200" 
                 data-allowed-types="image/jpeg">
                <input type="file" 
                       name="module_image" 
                       accept="image/jpeg"
                       style="display: none;">
                <div class="upload-text">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Click to upload image</p>
                    <small>JPG only, max 300KB</small>
                </div>
            </div>
            
            <?php if (!$slideshowEnabled && !empty($images)): ?>
                <div class="current-image mt-3">
                    <label>Current Image:</label>
                    <div class="image-preview">
                        <img src="<?= UPLOADS_URL ?>/images/<?= e($images[0]['image_path']) ?>" 
                             alt="Current image" 
                             style="max-width: 300px; max-height: 200px; border-radius: 8px; object-fit: cover;">
                        <br>
                        <button type="button" 
                                class="btn btn-danger btn-sm mt-2" 
                                onclick="deleteModuleImage(<?= e($module['id']) ?>)">
                            <i class="fas fa-trash"></i> Delete Image
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label for="image_width">Image Width</label>
                <select id="image_width" name="image_width" class="form-control form-select">
                    <option value="100%" <?= ($imageData['width'] ?? '100%') === '100%' ? 'selected' : '' ?>>100% (Full Width)</option>
                    <option value="75%" <?= ($imageData['width'] ?? '') === '75%' ? 'selected' : '' ?>>75%</option>
                    <option value="50%" <?= ($imageData['width'] ?? '') === '50%' ? 'selected' : '' ?>>50%</option>
                    <option value="25%" <?= ($imageData['width'] ?? '') === '25%' ? 'selected' : '' ?>>25%</option>
                </select>
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" 
                           id="as_background" 
                           name="as_background" 
                           class="form-check-input"
                           <?= ($imageData['as_background'] ?? false) ? 'checked' : '' ?>
                           onchange="toggleBackgroundOptions()">
                    <label for="as_background" class="form-check-label">
                        Use as Background Image
                    </label>
                </div>
            </div>
        </div>
        
        <div id="backgroundOptions" class="form-grid" <?= !($imageData['as_background'] ?? false) ? 'style="display: none;"' : '' ?>>
            <div class="form-group">
                <label for="background_size">Background Size</label>
                <select id="background_size" name="background_size" class="form-control form-select">
                    <option value="cover" <?= ($imageData['background_size'] ?? 'cover') === 'cover' ? 'selected' : '' ?>>Cover</option>
                    <option value="contain" <?= ($imageData['background_size'] ?? '') === 'contain' ? 'selected' : '' ?>>Contain</option>
                    <option value="auto" <?= ($imageData['background_size'] ?? '') === 'auto' ? 'selected' : '' ?>>Auto</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="background_position">Background Position</label>
                <select id="background_position" name="background_position" class="form-control form-select">
                    <option value="center center" <?= ($imageData['background_position'] ?? 'center center') === 'center center' ? 'selected' : '' ?>>Center Center</option>
                    <option value="center top" <?= ($imageData['background_position'] ?? '') === 'center top' ? 'selected' : '' ?>>Center Top</option>
                    <option value="center bottom" <?= ($imageData['background_position'] ?? '') === 'center bottom' ? 'selected' : '' ?>>Center Bottom</option>
                    <option value="left center" <?= ($imageData['background_position'] ?? '') === 'left center' ? 'selected' : '' ?>>Left Center</option>
                    <option value="right center" <?= ($imageData['background_position'] ?? '') === 'right center' ? 'selected' : '' ?>>Right Center</option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Slideshow Mode -->
    <div id="slideshowMode" class="image-mode" <?= !$slideshowEnabled ? 'style="display: none;"' : '' ?>>
        <div class="slideshow-images">
            <h4>Slideshow Images (Upload up to 10 images)</h4>
            
            <?php if ($slideshowEnabled && !empty($images)): ?>
                <div class="current-slideshow-images">
                    <label>Current Slideshow Images:</label>
                    <div class="slideshow-grid">
                        <?php foreach ($images as $index => $image): ?>
                            <div class="slideshow-image-item">
                                <img src="<?= UPLOADS_URL ?>/images/<?= e($image['image_path']) ?>" 
                                     alt="Slideshow image <?= $index + 1 ?>" 
                                     style="width: 150px; height: 100px; object-fit: cover; border-radius: 6px;">
                                <div class="image-order">Image <?= $index + 1 ?></div>
                                <button type="button" 
                                        class="btn btn-danger btn-sm" 
                                        onclick="deleteSlideshowImage(<?= e($image['id']) ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="slideshow-upload-grid">
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <div class="slideshow-upload-item">
                        <label>Image <?= $i ?></label>
                        <div class="file-upload-area small" 
                             data-max-size="307200" 
                             data-allowed-types="image/jpeg">
                            <input type="file" 
                                   name="slideshow_image_<?= $i ?>" 
                                   accept="image/jpeg"
                                   style="display: none;">
                            <div class="upload-text">
                                <i class="fas fa-plus"></i>
                                <small>Upload</small>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <div class="form-group mt-3">
            <label for="slideshow_settings">Slideshow Settings</label>
            <div class="form-grid">
                <div class="form-group">
                    <label for="autoplay_speed">Autoplay Speed (seconds)</label>
                    <select id="autoplay_speed" name="autoplay_speed" class="form-control form-select">
                        <option value="3">3 seconds</option>
                        <option value="5" selected>5 seconds</option>
                        <option value="7">7 seconds</option>
                        <option value="10">10 seconds</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" id="show_dots" name="show_dots" class="form-check-input" checked>
                        <label for="show_dots" class="form-check-label">Show Navigation Dots</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSlideshowMode() {
    const slideshowEnabled = document.getElementById('slideshow_enabled').checked;
    const singleMode = document.getElementById('singleImageMode');
    const slideshowMode = document.getElementById('slideshowMode');
    
    if (slideshowEnabled) {
        singleMode.style.display = 'none';
        slideshowMode.style.display = 'block';
    } else {
        singleMode.style.display = 'block';
        slideshowMode.style.display = 'none';
    }
}

function toggleBackgroundOptions() {
    const asBackground = document.getElementById('as_background').checked;
    const bgOptions = document.getElementById('backgroundOptions');
    
    if (asBackground) {
        bgOptions.style.display = 'grid';
    } else {
        bgOptions.style.display = 'none';
    }
}

function deleteSlideshowImage(imageId) {
    if (!confirm('Are you sure you want to delete this image?')) {
        return;
    }
    
    AdminCMS.ajax('ajax/delete_slideshow_image.php', {
        method: 'POST',
        body: JSON.stringify({
            image_id: imageId
        })
    }).then(response => {
        if (response.success) {
            location.reload();
        } else {
            AdminCMS.showAlert('Failed to delete image', 'error');
        }
    });
}
</script>

<style>
.image-mode {
    padding: 20px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
}

.form-check {
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-check-input {
    margin: 0;
}

.slideshow-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    margin: 15px 0;
}

.slideshow-image-item {
    text-align: center;
    background: white;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.image-order {
    font-size: 12px;
    color: #6c757d;
    margin: 5px 0;
}

.slideshow-upload-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.slideshow-upload-item {
    text-align: center;
}

.slideshow-upload-item label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

.file-upload-area.small {
    padding: 15px;
    min-height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.file-upload-area.small .upload-text {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.file-upload-area.small i {
    font-size: 1.2rem;
}

@media (max-width: 768px) {
    .slideshow-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
    
    .slideshow-upload-grid {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }
}
</style>