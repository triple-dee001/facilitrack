<?php
/**
 * Technician Dashboard
 * Shows tasks assigned to the logged-in technician
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['technician']);

$page_title = 'My Dashboard';
$user = current_user();
$db = getDBConnection();
$org_id = get_org_id();

// Get counts of assigned tasks
$stmt = $db->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM maintenance_requests WHERE assigned_to = ? AND organization_id = ?");
$stmt->execute([$user['id'], $org_id]);
$counts = $stmt->fetch();

// Get assigned tasks
$stmt = $db->prepare("SELECT mr.*, u.full_name as reporter_name, u.location as reporter_location 
                       FROM maintenance_requests mr 
                       JOIN users u ON mr.user_id = u.id 
                       WHERE mr.assigned_to = ? AND mr.organization_id = ?
                       ORDER BY 
                           CASE mr.priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 END,
                           mr.created_at DESC");
$stmt->execute([$user['id'], $org_id]);
$tasks = $stmt->fetchAll();

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
        <div class="stat-icon"><i class="fas fa-tasks"></i></div>
        <div class="stat-info">
            <h3><?php echo $counts['total'] ?? 0; ?></h3>
            <p>Total Assigned</p>
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
            <p>Completed</p>
        </div>
    </div>
</div>

<!-- Assigned Tasks -->
<div class="section-header">
    <h2><i class="fas fa-clipboard-list"></i> Assigned Tasks</h2>
</div>

<?php if (empty($tasks)): ?>
<div class="empty-state">
    <i class="fas fa-inbox"></i>
    <h3>No tasks assigned</h3>
    <p>Tasks assigned to you by the facility manager will appear here.</p>
</div>
<?php else: ?>
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
