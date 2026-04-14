<?php
/**
 * New Maintenance Request Form
 * Now includes issue_location field separate from user's base location
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['user']);

$page_title = 'Report New Issue';

// Retrieve stored form data on validation failure
$form_data = $_SESSION['form_data'] ?? [];
$form_errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <div class="form-card">
        <div class="form-card-header">
            <h2><i class="fas fa-tools"></i> Report Maintenance Issue</h2>
            <p>Fill in the details below to submit a maintenance request.</p>
        </div>
        
        <?php if (!empty($form_errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <ul>
                <?php foreach ($form_errors as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo APP_URL; ?>/api/submit-request.php" enctype="multipart/form-data" id="newRequestForm">
            <div class="form-group">
                <label for="title">
                    <i class="fas fa-heading"></i> Issue Title <span class="required">*</span>
                </label>
                <input type="text" id="title" name="title" placeholder="e.g. Leaking pipe in bathroom" required maxlength="200"
                       value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>">
                <span class="char-counter"><span id="titleCount">0</span>/200</span>
            </div>
            
            <div class="form-group">
                <label for="issue_location">
                    <i class="fas fa-map-marker-alt"></i> Issue Location <span class="required">*</span>
                </label>
                <input type="text" id="issue_location" name="issue_location" 
                       placeholder="e.g. 3rd Floor Bathroom, Block A Room 12, Parking Lot Area B" required maxlength="300"
                       value="<?php echo htmlspecialchars($form_data['issue_location'] ?? ''); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="category">
                        <i class="fas fa-tag"></i> Category <span class="required">*</span>
                    </label>
                    <select id="category" name="category" required>
                        <option value="">Select category</option>
                        <option value="plumbing" <?php echo ($form_data['category'] ?? '') === 'plumbing' ? 'selected' : ''; ?>>🔧 Plumbing</option>
                        <option value="electrical" <?php echo ($form_data['category'] ?? '') === 'electrical' ? 'selected' : ''; ?>>⚡ Electrical</option>
                        <option value="structural" <?php echo ($form_data['category'] ?? '') === 'structural' ? 'selected' : ''; ?>>🏗️ Structural</option>
                        <option value="cleaning" <?php echo ($form_data['category'] ?? '') === 'cleaning' ? 'selected' : ''; ?>>🧹 Cleaning</option>
                        <option value="pest_control" <?php echo ($form_data['category'] ?? '') === 'pest_control' ? 'selected' : ''; ?>>🐛 Pest Control</option>
                        <option value="networking" <?php echo ($form_data['category'] ?? '') === 'networking' ? 'selected' : ''; ?>>🌐 Networking / Internet</option>
                        <option value="furniture" <?php echo ($form_data['category'] ?? '') === 'furniture' ? 'selected' : ''; ?>>🪑 Furniture</option>
                        <option value="security" <?php echo ($form_data['category'] ?? '') === 'security' ? 'selected' : ''; ?>>🔒 Security Systems</option>
                        <option value="equipment" <?php echo ($form_data['category'] ?? '') === 'equipment' ? 'selected' : ''; ?>>⚙️ Equipment</option>
                        <option value="other" <?php echo ($form_data['category'] ?? '') === 'other' ? 'selected' : ''; ?>>📋 Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="priority">
                        <i class="fas fa-exclamation-triangle"></i> Priority <span class="required">*</span>
                    </label>
                    <select id="priority" name="priority" required>
                        <option value="low" <?php echo ($form_data['priority'] ?? 'medium') === 'low' ? 'selected' : ''; ?>>🟢 Low</option>
                        <option value="medium" <?php echo ($form_data['priority'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>🟡 Medium</option>
                        <option value="high" <?php echo ($form_data['priority'] ?? 'medium') === 'high' ? 'selected' : ''; ?>>🟠 High</option>
                        <option value="critical" <?php echo ($form_data['priority'] ?? 'medium') === 'critical' ? 'selected' : ''; ?>>🔴 Critical</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">
                    <i class="fas fa-align-left"></i> Description <span class="required">*</span>
                </label>
                <textarea id="description" name="description" rows="5" placeholder="Describe the issue in detail — what is happening, how long has it been occurring..." required maxlength="2000"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                <span class="char-counter"><span id="descCount">0</span>/2000</span>
            </div>
            
            <div class="form-group">
                <label for="image">
                    <i class="fas fa-camera"></i> Attach Photo (optional)
                </label>
                <div class="file-upload-area" id="fileUploadArea">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Click or drag an image here</p>
                    <small>JPEG, PNG, GIF, WebP — Max 5MB</small>
                    <input type="file" id="image" name="image" accept="image/*" class="file-input">
                </div>
                <div class="image-preview" id="imagePreview" style="display:none;">
                    <img id="previewImg" src="" alt="Preview">
                    <button type="button" class="btn btn-sm btn-danger" onclick="clearImagePreview()">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
