<?php
/**
 * View Single Request — detailed view with activity log
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['user']);

$user = current_user();
$db = getDBConnection();
$org_id = get_org_id();

$request_id = intval($_GET['id'] ?? 0);

// Get request (only if it belongs to this user and same org)
$stmt = $db->prepare("SELECT mr.*, u.full_name as assigned_name 
                       FROM maintenance_requests mr 
                       LEFT JOIN users u ON mr.assigned_to = u.id 
                       WHERE mr.id = ? AND mr.user_id = ? AND mr.organization_id = ?");
$stmt->execute([$request_id, $user['id'], $org_id]);
$request = $stmt->fetch();

if (!$request) {
    $_SESSION['error_message'] = 'Request not found or access denied.';
    header('Location: ' . APP_URL . '/tenant/dashboard.php');
    exit();
}

// Get update history
$stmt = $db->prepare("SELECT ru.*, u.full_name as updater_name 
                       FROM request_updates ru 
                       JOIN users u ON ru.updated_by = u.id 
                       WHERE ru.request_id = ? 
                       ORDER BY ru.created_at DESC");
$stmt->execute([$request_id]);
$updates = $stmt->fetchAll();

$page_title = 'Request #' . $request_id;
require_once __DIR__ . '/../includes/header.php';
?>

<a href="my-requests.php" class="btn btn-outline btn-back">
    <i class="fas fa-arrow-left"></i> Back to My Reports
</a>

<div class="detail-container">
    <div class="detail-card">
        <div class="detail-header">
            <div>
                <h2><?php echo htmlspecialchars($request['title']); ?></h2>
                <span class="detail-id">Request #<?php echo $request['id']; ?></span>
            </div>
            <div class="detail-badges">
                <span class="badge badge-<?php echo $request['status']; ?> badge-lg">
                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                </span>
                <span class="badge badge-priority-<?php echo $request['priority']; ?> badge-lg">
                    <?php echo ucfirst($request['priority']); ?> Priority
                </span>
            </div>
        </div>
        
        <div class="detail-grid">
            <div class="detail-info">
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-map-marker-alt"></i> Issue Location</span>
                    <span class="info-value"><?php echo htmlspecialchars($request['issue_location']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-tag"></i> Category</span>
                    <span class="info-value"><?php echo ucfirst(str_replace('_', ' ', $request['category'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-calendar-plus"></i> Submitted</span>
                    <span class="info-value"><?php echo date('F d, Y h:i A', strtotime($request['created_at'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-calendar-check"></i> Last Updated</span>
                    <span class="info-value"><?php echo date('F d, Y h:i A', strtotime($request['updated_at'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-user-cog"></i> Assigned To</span>
                    <span class="info-value"><?php echo $request['assigned_name'] ? htmlspecialchars($request['assigned_name']) : '<em>Not yet assigned</em>'; ?></span>
                </div>
            </div>
            
            <div class="detail-description">
                <h3><i class="fas fa-align-left"></i> Description</h3>
                <p><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
            </div>
            
            <?php if (!empty($request['image_path'])): ?>
            <div class="detail-image">
                <h3><i class="fas fa-image"></i> Attached Photo</h3>
                <img src="<?php echo APP_URL . '/' . $request['image_path']; ?>" alt="Issue image" class="full-image">
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Activity Log -->
    <div class="detail-card">
        <h2><i class="fas fa-history"></i> Activity Log</h2>
        
        <?php if (empty($updates)): ?>
        <div class="empty-state small">
            <p>No updates yet. You will see progress here when the facility team responds.</p>
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
                        Status changed from 
                        <span class="badge badge-<?php echo $update['old_status']; ?> badge-sm"><?php echo ucfirst(str_replace('_', ' ', $update['old_status'])); ?></span>
                        to 
                        <span class="badge badge-<?php echo $update['new_status']; ?> badge-sm"><?php echo ucfirst(str_replace('_', ' ', $update['new_status'])); ?></span>
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
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
