<?php
/**
 * My Requests — list all requests by the user
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['user']);

$page_title = 'My Reports';
$user = current_user();
$db = getDBConnection();
$org_id = get_org_id();

// Filter by status
$status_filter = $_GET['status'] ?? 'all';
$valid_filters = ['all', 'pending', 'in_progress', 'resolved', 'closed'];
if (!in_array($status_filter, $valid_filters)) $status_filter = 'all';

if ($status_filter === 'all') {
    $stmt = $db->prepare("SELECT * FROM maintenance_requests WHERE user_id = ? AND organization_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id'], $org_id]);
} else {
    $stmt = $db->prepare("SELECT * FROM maintenance_requests WHERE user_id = ? AND organization_id = ? AND status = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id'], $org_id, $status_filter]);
}
$requests = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Filter Tabs -->
<div class="filter-tabs">
    <a href="?status=all" class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
    <a href="?status=pending" class="filter-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
        <i class="fas fa-clock"></i> Pending
    </a>
    <a href="?status=in_progress" class="filter-tab <?php echo $status_filter === 'in_progress' ? 'active' : ''; ?>">
        <i class="fas fa-wrench"></i> In Progress
    </a>
    <a href="?status=resolved" class="filter-tab <?php echo $status_filter === 'resolved' ? 'active' : ''; ?>">
        <i class="fas fa-check"></i> Resolved
    </a>
    <a href="?status=closed" class="filter-tab <?php echo $status_filter === 'closed' ? 'active' : ''; ?>">
        <i class="fas fa-lock"></i> Closed
    </a>
</div>

<?php if (empty($requests)): ?>
<div class="empty-state">
    <i class="fas fa-inbox"></i>
    <h3>No reports found</h3>
    <p>No maintenance reports match the selected filter.</p>
    <a href="new-request.php" class="btn btn-primary">
        <i class="fas fa-plus-circle"></i> Report Issue
    </a>
</div>
<?php else: ?>
<div class="results-count">
    <p>Showing <?php echo count($requests); ?> report(s)</p>
</div>

<div class="request-cards">
    <?php foreach ($requests as $req): ?>
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
        <p class="request-desc"><?php echo htmlspecialchars(substr($req['description'], 0, 120)); ?>...</p>
        <div class="request-meta">
            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($req['issue_location']); ?></span>
            <span><i class="fas fa-tag"></i> <?php echo ucfirst(str_replace('_', ' ', $req['category'])); ?></span>
            <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y h:i A', strtotime($req['created_at'])); ?></span>
        </div>
        <?php if (!empty($req['image_path'])): ?>
        <div class="request-image-thumb">
            <img src="<?php echo APP_URL . '/' . $req['image_path']; ?>" alt="Issue image">
        </div>
        <?php endif; ?>
        <a href="view-request.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-outline">View Details</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
