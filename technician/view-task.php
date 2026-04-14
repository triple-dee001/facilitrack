<?php
/**
 * Technician — View and Update Assigned Task
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['technician']);

$user = current_user();
$db = getDBConnection();
$org_id = get_org_id();

$task_id = intval($_GET['id'] ?? 0);

// Get task (only if assigned to this technician and in same org)
$stmt = $db->prepare("SELECT mr.*, u.full_name as reporter_name, u.email as reporter_email, u.phone as reporter_phone, u.location as reporter_location, u.department as reporter_dept
                       FROM maintenance_requests mr 
                       JOIN users u ON mr.user_id = u.id 
                       WHERE mr.id = ? AND mr.assigned_to = ? AND mr.organization_id = ?");
$stmt->execute([$task_id, $user['id'], $org_id]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error_message'] = 'Task not found or not assigned to you.';
    header('Location: ' . APP_URL . '/technician/dashboard.php');
    exit();
}

// Handle status update from technician
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'] ?? '';
    $comment = trim($_POST['comment'] ?? '');
    
    $valid_statuses = ['in_progress', 'resolved'];
    if (in_array($new_status, $valid_statuses)) {
        $old_status = $task['status'];
        
        $stmt = $db->prepare("UPDATE maintenance_requests SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $task_id]);
        
        // Log the update
        $stmt = $db->prepare("INSERT INTO request_updates (request_id, updated_by, old_status, new_status, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$task_id, $user['id'], $old_status, $new_status, $comment]);
        
        $_SESSION['success_message'] = 'Task updated successfully!';
        header('Location: ' . APP_URL . '/technician/view-task.php?id=' . $task_id);
        exit();
    }
}

// Get update history
$stmt = $db->prepare("SELECT ru.*, u.full_name as updater_name 
                       FROM request_updates ru 
                       JOIN users u ON ru.updated_by = u.id 
                       WHERE ru.request_id = ? 
                       ORDER BY ru.created_at DESC");
$stmt->execute([$task_id]);
$updates = $stmt->fetchAll();

$page_title = 'Task #' . $task_id;

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

require_once __DIR__ . '/../includes/header.php';
?>

<a href="dashboard.php" class="btn btn-outline btn-back">
    <i class="fas fa-arrow-left"></i> Back to My Tasks
</a>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="manage-grid">
    <!-- Left: Task Details -->
    <div class="detail-card">
        <div class="detail-header">
            <div>
                <h2><?php echo htmlspecialchars($task['title']); ?></h2>
                <span class="detail-id">Task #<?php echo $task['id']; ?></span>
            </div>
            <div class="detail-badges">
                <span class="badge badge-<?php echo $task['status']; ?> badge-lg">
                    <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                </span>
                <span class="badge badge-priority-<?php echo $task['priority']; ?> badge-lg">
                    <?php echo ucfirst($task['priority']); ?>
                </span>
            </div>
        </div>
        
        <div class="detail-grid">
            <div class="detail-info">
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-map-marker-alt"></i> Issue Location</span>
                    <span class="info-value"><?php echo htmlspecialchars($task['issue_location']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-user"></i> Reported By</span>
                    <span class="info-value"><?php echo htmlspecialchars($task['reporter_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-phone"></i> Reporter Phone</span>
                    <span class="info-value"><?php echo htmlspecialchars($task['reporter_phone']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-tag"></i> Category</span>
                    <span class="info-value"><?php echo ucfirst(str_replace('_', ' ', $task['category'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-calendar-plus"></i> Submitted</span>
                    <span class="info-value"><?php echo date('F d, Y h:i A', strtotime($task['created_at'])); ?></span>
                </div>
            </div>
            
            <div class="detail-description">
                <h3><i class="fas fa-align-left"></i> Description</h3>
                <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
            </div>
            
            <?php if (!empty($task['image_path'])): ?>
            <div class="detail-image">
                <h3><i class="fas fa-image"></i> Attached Photo</h3>
                <img src="<?php echo APP_URL . '/' . $task['image_path']; ?>" alt="Issue image" class="full-image">
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right: Update Form (Technician) -->
    <div class="detail-card update-card">
        <h2><i class="fas fa-edit"></i> Update Task</h2>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="status"><i class="fas fa-info-circle"></i> Update Status</label>
                <select id="status" name="status">
                    <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>🔧 In Progress</option>
                    <option value="resolved" <?php echo $task['status'] === 'resolved' ? 'selected' : ''; ?>>✅ Resolved</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="comment"><i class="fas fa-comment"></i> Comment / Note</label>
                <textarea id="comment" name="comment" rows="3" placeholder="e.g. Replaced the broken part, waiting for spare parts..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-save"></i> Save Update
            </button>
        </form>
    </div>
</div>

<!-- Activity Log -->
<div class="detail-card">
    <h2><i class="fas fa-history"></i> Activity Log</h2>
    <?php if (empty($updates)): ?>
    <div class="empty-state small">
        <p>No updates yet. Update the status as you work on this task.</p>
    </div>
    <?php else: ?>
    <div class="timeline">
        <?php foreach ($updates as $update): ?>
        <div class="timeline-item">
            <div class="timeline-dot dot-<?php echo $update['new_status']; ?>"></div>
            <div class="timeline-content">
                <div class="timeline-header">
                    <strong><?php echo htmlspecialchars($update['updater_name']); ?></strong>
                    <span class="timeline-date"><?php echo date('M d, Y h:i A', strtotime($update['created_at'])); ?></span>
                </div>
                <p>
                    Status: <span class="badge badge-<?php echo $update['old_status']; ?> badge-sm"><?php echo ucfirst(str_replace('_', ' ', $update['old_status'])); ?></span>
                    → <span class="badge badge-<?php echo $update['new_status']; ?> badge-sm"><?php echo ucfirst(str_replace('_', ' ', $update['new_status'])); ?></span>
                </p>
                <?php if (!empty($update['comment'])): ?>
                <div class="timeline-comment">
                    <i class="fas fa-comment"></i> <?php echo nl2br(htmlspecialchars($update['comment'])); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
