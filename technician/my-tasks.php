<?php
/**
 * Technician — My Tasks (list view with filters)
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['technician']);

$page_title = 'My Tasks';
$user = current_user();
$db = getDBConnection();
$org_id = get_org_id();

// Filter by status
$status_filter = $_GET['status'] ?? 'all';
$valid_filters = ['all', 'pending', 'in_progress', 'resolved', 'closed'];
if (!in_array($status_filter, $valid_filters)) $status_filter = 'all';

if ($status_filter === 'all') {
    $stmt = $db->prepare("SELECT mr.*, u.full_name as reporter_name FROM maintenance_requests mr JOIN users u ON mr.user_id = u.id WHERE mr.assigned_to = ? AND mr.organization_id = ? ORDER BY mr.created_at DESC");
    $stmt->execute([$user['id'], $org_id]);
} else {
    $stmt = $db->prepare("SELECT mr.*, u.full_name as reporter_name FROM maintenance_requests mr JOIN users u ON mr.user_id = u.id WHERE mr.assigned_to = ? AND mr.organization_id = ? AND mr.status = ? ORDER BY mr.created_at DESC");
    $stmt->execute([$user['id'], $org_id, $status_filter]);
}
$tasks = $stmt->fetchAll();

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

<?php if (empty($tasks)): ?>
<div class="empty-state">
    <i class="fas fa-inbox"></i>
    <h3>No tasks found</h3>
    <p>No tasks matching the selected filter.</p>
</div>
<?php else: ?>
<div class="results-count">
    <p>Showing <?php echo count($tasks); ?> task(s)</p>
</div>
<div class="request-cards">
    <?php foreach ($tasks as $task): ?>
    <div class="request-card">
        <div class="request-card-header">
            <span class="badge badge-<?php echo $task['status']; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
            </span>
            <span class="badge badge-priority-<?php echo $task['priority']; ?>">
                <?php echo ucfirst($task['priority']); ?>
            </span>
        </div>
        <h3 class="request-title"><?php echo htmlspecialchars($task['title']); ?></h3>
        <p class="request-desc"><?php echo htmlspecialchars(substr($task['description'], 0, 120)); ?>...</p>
        <div class="request-meta">
            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($task['issue_location']); ?></span>
            <span><i class="fas fa-tag"></i> <?php echo ucfirst(str_replace('_', ' ', $task['category'])); ?></span>
            <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($task['created_at'])); ?></span>
        </div>
        <a href="view-task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-eye"></i> View Task
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
