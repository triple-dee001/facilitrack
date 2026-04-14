<?php
/**
 * User Dashboard
 * Shows summary stats and recent requests for the logged-in user
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['user']);

$page_title = 'My Dashboard';
$user = current_user();
$db = getDBConnection();
$org_id = get_org_id();

// Get counts
$stmt = $db->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM maintenance_requests WHERE user_id = ? AND organization_id = ?");
$stmt->execute([$user['id'], $org_id]);
$counts = $stmt->fetch();

// Recent requests
$stmt = $db->prepare("SELECT * FROM maintenance_requests WHERE user_id = ? AND organization_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user['id'], $org_id]);
$recent = $stmt->fetchAll();

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="stat-cards">
    <div class="stat-card stat-total">
        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
        <div class="stat-info">
            <h3><?php echo $counts['total'] ?? 0; ?></h3>
            <p>Total Reports</p>
        </div>
    </div>
    <div class="stat-card stat-pending">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3><?php echo $counts['pending'] ?? 0; ?></h3>
            <p>Pending</p>
        </div>
    </div>
    <div class="stat-card stat-progress">
        <div class="stat-icon"><i class="fas fa-spinner"></i></div>
        <div class="stat-info">
            <h3><?php echo $counts['in_progress'] ?? 0; ?></h3>
            <p>In Progress</p>
        </div>
    </div>
    <div class="stat-card stat-resolved">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3><?php echo $counts['resolved'] ?? 0; ?></h3>
            <p>Resolved</p>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="new-request.php" class="action-card">
        <i class="fas fa-plus-circle"></i>
        <span>Report Issue</span>
    </a>
    <a href="my-requests.php" class="action-card">
        <i class="fas fa-clipboard-list"></i>
        <span>View All Reports</span>
    </a>
</div>

<!-- Recent Requests -->
<div class="section-header">
    <h2><i class="fas fa-history"></i> Recent Reports</h2>
    <a href="my-requests.php" class="btn btn-outline btn-sm">View All</a>
</div>

<?php if (empty($recent)): ?>
<div class="empty-state">
    <i class="fas fa-inbox"></i>
    <h3>No reports yet</h3>
    <p>Submit your first maintenance report to get started.</p>
    <a href="new-request.php" class="btn btn-primary">
        <i class="fas fa-plus-circle"></i> Report Issue
    </a>
</div>
<?php else: ?>
<div class="request-cards">
    <?php foreach ($recent as $req): ?>
    <div class="request-card">
        <div class="request-card-header">
            <span class="badge badge-<?php echo $req['status']; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?>
            </span>
            <span class="badge badge-priority-<?php echo $req['priority']; ?>">
                <?php echo ucfirst($req['priority']); ?>
            </span>
        </div>
        <h3 class="request-title"><?php echo htmlspecialchars($req['title']); ?></h3>
        <p class="request-desc"><?php echo htmlspecialchars(substr($req['description'], 0, 100)); ?>...</p>
        <div class="request-meta">
            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($req['issue_location']); ?></span>
            <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($req['created_at'])); ?></span>
        </div>
        <a href="view-request.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-outline">View Details</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
