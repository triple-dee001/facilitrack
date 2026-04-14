<?php
/**
 * Admin/Manager Dashboard
 * Shows overall stats, category breakdown, recent requests
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['manager', 'admin']);

$page_title = 'Dashboard';
$user = current_user();
$db = getDBConnection();
$org_id = get_org_id();

// Get overall counts (org-scoped)
$stmt = $db->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical
    FROM maintenance_requests WHERE organization_id = ?");
$stmt->execute([$org_id]);
$counts = $stmt->fetch();

// User count in this org
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE organization_id = ? AND role = 'user'");
$stmt->execute([$org_id]);
$user_count = $stmt->fetch()['total'];

// Technician count in this org
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE organization_id = ? AND role = 'technician'");
$stmt->execute([$org_id]);
$tech_count = $stmt->fetch()['total'];

// Pending approval count
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE organization_id = ? AND is_approved = 0");
$stmt->execute([$org_id]);
$pending_count = $stmt->fetch()['total'];

// Category breakdown
$stmt = $db->prepare("SELECT category, COUNT(*) as count FROM maintenance_requests WHERE organization_id = ? GROUP BY category ORDER BY count DESC");
$stmt->execute([$org_id]);
$categories = $stmt->fetchAll();

// Recent requests
$stmt = $db->prepare("SELECT mr.*, u.full_name as reporter_name 
                       FROM maintenance_requests mr 
                       JOIN users u ON mr.user_id = u.id 
                       WHERE mr.organization_id = ?
                       ORDER BY mr.created_at DESC LIMIT 5");
$stmt->execute([$org_id]);
$recent = $stmt->fetchAll();

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($pending_count > 0): ?>
<div class="alert alert-warning">
    <i class="fas fa-user-clock"></i>
    <strong><?php echo $pending_count; ?> pending registration(s)</strong> awaiting your approval.
    <a href="users.php?filter=pending" class="btn btn-sm btn-warning" style="margin-left: 10px;">
        <i class="fas fa-user-check"></i> Review Now
    </a>
</div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="stat-cards">
    <div class="stat-card stat-total">
        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
        <div class="stat-info">
            <h3><?php echo $counts['total'] ?? 0; ?></h3>
            <p>Total Requests</p>
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
        <div class="stat-icon"><i class="fas fa-wrench"></i></div>
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
    <div class="stat-card stat-critical">
        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-info">
            <h3><?php echo $counts['critical'] ?? 0; ?></h3>
            <p>Critical Issues</p>
        </div>
    </div>
    <div class="stat-card stat-users">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?php echo $user_count; ?> / <?php echo $tech_count; ?></h3>
            <p>Users / Technicians</p>
        </div>
    </div>
</div>

<!-- Organization Info -->
<?php if ($user['role'] === 'admin'): ?>
<div class="org-info-card">
    <div class="org-info-content">
        <i class="fas fa-building"></i>
        <div>
            <strong><?php echo htmlspecialchars($user['org_name']); ?></strong>
            <span class="org-code">Organization Code: <code><?php echo htmlspecialchars($user['org_code']); ?></code></span>
        </div>
    </div>
    <small>Share this code with staff so they can register on FaciliTrack</small>
</div>
<?php endif; ?>

<!-- Category Breakdown -->
<?php if (!empty($categories)): ?>
<div class="section-header">
    <h2><i class="fas fa-th-large"></i> Requests by Category</h2>
</div>
<div class="category-cards">
    <?php 
    $cat_icons = [
        'plumbing' => '🔧', 'electrical' => '⚡', 'structural' => '🏗️', 'cleaning' => '🧹',
        'pest_control' => '🐛', 'networking' => '🌐', 'furniture' => '🪑', 'security' => '🔒',
        'equipment' => '⚙️', 'other' => '📋'
    ];
    foreach ($categories as $cat): ?>
    <div class="category-card">
        <span class="cat-icon"><?php echo $cat_icons[$cat['category']] ?? '📋'; ?></span>
        <span class="cat-name"><?php echo ucfirst(str_replace('_', ' ', $cat['category'])); ?></span>
        <span class="cat-count"><?php echo $cat['count']; ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Recent Requests -->
<div class="section-header">
    <h2><i class="fas fa-history"></i> Recent Requests</h2>
    <a href="requests.php" class="btn btn-outline btn-sm">View All</a>
</div>

<?php if (empty($recent)): ?>
<div class="empty-state">
    <i class="fas fa-inbox"></i>
    <h3>No requests yet</h3>
    <p>Requests from users in your organization will appear here.</p>
</div>
<?php else: ?>
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Reported By</th>
                <th>Location</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent as $req): ?>
            <tr>
                <td><?php echo $req['id']; ?></td>
                <td class="title-cell"><?php echo htmlspecialchars(substr($req['title'], 0, 50)); ?></td>
                <td><?php echo htmlspecialchars($req['reporter_name']); ?></td>
                <td><?php echo htmlspecialchars($req['issue_location']); ?></td>
                <td><span class="badge badge-priority-<?php echo $req['priority']; ?>"><?php echo ucfirst($req['priority']); ?></span></td>
                <td><span class="badge badge-<?php echo $req['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?></span></td>
                <td><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
                <td>
                    <a href="manage-request.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-cog"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
