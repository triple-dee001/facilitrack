<?php
/**
 * Manage Single Request — admin/manager can update status, assign to technician, comment
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['manager', 'admin']);

$db = getDBConnection();
$org_id = get_org_id();
$request_id = intval($_GET['id'] ?? 0);

// Get request details (org-scoped)
$stmt = $db->prepare("SELECT mr.*, u.full_name as reporter_name, u.email as reporter_email, u.phone as reporter_phone, u.location as reporter_location, u.department as reporter_dept,
                              a.full_name as assigned_name
                       FROM maintenance_requests mr 
                       JOIN users u ON mr.user_id = u.id 
                       LEFT JOIN users a ON mr.assigned_to = a.id
                       WHERE mr.id = ? AND mr.organization_id = ?");
$stmt->execute([$request_id, $org_id]);
$request = $stmt->fetch();

if (!$request) {
    $_SESSION['error_message'] = 'Request not found.';
    header('Location: ' . APP_URL . '/admin/requests.php');
    exit();
}

// Get technicians for assignment dropdown (within this org)
$stmt = $db->prepare("SELECT id, full_name, role, department FROM users WHERE role = 'technician' AND is_active = 1 AND organization_id = ? ORDER BY full_name");
$stmt->execute([$org_id]);
$technicians = $stmt->fetchAll();

// Get update history
$stmt = $db->prepare("SELECT ru.*, u.full_name as updater_name 
                       FROM request_updates ru 
                       JOIN users u ON ru.updated_by = u.id 
                       WHERE ru.request_id = ? 
                       ORDER BY ru.created_at DESC");
$stmt->execute([$request_id]);
$updates = $stmt->fetchAll();

$page_title = 'Manage Request #' . $request_id;

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

require_once __DIR__ . '/../includes/header.php';
?>

<a href="requests.php" class="btn btn-outline btn-back">
    <i class="fas fa-arrow-left"></i> Back to All Requests
</a>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="manage-grid">
    <!-- Left: Request Details -->
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
                    <?php echo ucfirst($request['priority']); ?>
                </span>
            </div>
        </div>
        
        <div class="detail-grid">
            <div class="detail-info">
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-user"></i> Reported By</span>
                    <span class="info-value"><?php echo htmlspecialchars($request['reporter_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-map-marker-alt"></i> Issue Location</span>
                    <span class="info-value"><?php echo htmlspecialchars($request['issue_location']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-map-pin"></i> Reporter Location</span>
                    <span class="info-value"><?php echo htmlspecialchars($request['reporter_location']); ?></span>
                </div>
                <?php if (!empty($request['reporter_dept'])): ?>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-sitemap"></i> Department</span>
                    <span class="info-value"><?php echo htmlspecialchars($request['reporter_dept']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($request['reporter_email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-phone"></i> Phone</span>
                    <span class="info-value"><?php echo htmlspecialchars($request['reporter_phone']); ?></span>
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
                    <span class="info-label"><i class="fas fa-user-cog"></i> Assigned To</span>
                    <span class="info-value"><?php echo $request['assigned_name'] ? htmlspecialchars($request['assigned_name']) : '<em>Unassigned</em>'; ?></span>
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
    
    <!-- Right: Update Form -->
    <div class="detail-card update-card">
        <h2><i class="fas fa-edit"></i> Update Request</h2>
        
        <form method="POST" action="<?php echo APP_URL; ?>/api/update-request.php" id="updateForm">
            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
            
            <div class="form-group">
                <label for="status"><i class="fas fa-info-circle"></i> Status</label>
                <select id="status" name="status">
                    <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                    <option value="in_progress" <?php echo $request['status'] === 'in_progress' ? 'selected' : ''; ?>>🔧 In Progress</option>
                    <option value="resolved" <?php echo $request['status'] === 'resolved' ? 'selected' : ''; ?>>✅ Resolved</option>
                    <option value="closed" <?php echo $request['status'] === 'closed' ? 'selected' : ''; ?>>🔒 Closed</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="assigned_to"><i class="fas fa-user-check"></i> Assign to Technician</label>
                <select id="assigned_to" name="assigned_to">
                    <option value="">-- Select Technician --</option>
                    <?php foreach ($technicians as $tech): ?>
                    <option value="<?php echo $tech['id']; ?>" <?php echo $request['assigned_to'] == $tech['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tech['full_name']); ?><?php echo $tech['department'] ? ' (' . htmlspecialchars($tech['department']) . ')' : ''; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="comment"><i class="fas fa-comment"></i> Comment / Note</label>
                <textarea id="comment" name="comment" rows="3" placeholder="Add a note about this update..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<!-- Activity Log -->
<div class="detail-card">
    <h2><i class="fas fa-history"></i> Activity Log</h2>
    <?php if (empty($updates)): ?>
    <div class="empty-state small">
        <p>No updates recorded yet.</p>
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
