<?php
/**
 * All Requests — filterable and searchable admin/manager view
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['manager', 'admin']);

$page_title = 'All Requests';
$db = getDBConnection();
$org_id = get_org_id();

// Filters
$status_filter = $_GET['status'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT mr.*, u.full_name as reporter_name, u.location as reporter_location
        FROM maintenance_requests mr
        JOIN users u ON mr.user_id = u.id WHERE mr.organization_id = ?";
$params = [$org_id];

if ($status_filter !== 'all') {
    $sql .= " AND mr.status = ?";
    $params[] = $status_filter;
}
if ($category_filter !== 'all') {
    $sql .= " AND mr.category = ?";
    $params[] = $category_filter;
}
if (!empty($search)) {
    $sql .= " AND (mr.title LIKE ? OR mr.description LIKE ? OR u.full_name LIKE ? OR mr.issue_location LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$sql .= " ORDER BY mr.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Search & Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="" class="filter-form">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Search by title, reporter, location..." 
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <select name="status">
            <option value="all">All Status</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
        </select>
        <select name="category">
            <option value="all">All Categories</option>
            <option value="plumbing" <?php echo $category_filter === 'plumbing' ? 'selected' : ''; ?>>Plumbing</option>
            <option value="electrical" <?php echo $category_filter === 'electrical' ? 'selected' : ''; ?>>Electrical</option>
            <option value="structural" <?php echo $category_filter === 'structural' ? 'selected' : ''; ?>>Structural</option>
            <option value="cleaning" <?php echo $category_filter === 'cleaning' ? 'selected' : ''; ?>>Cleaning</option>
            <option value="pest_control" <?php echo $category_filter === 'pest_control' ? 'selected' : ''; ?>>Pest Control</option>
            <option value="networking" <?php echo $category_filter === 'networking' ? 'selected' : ''; ?>>Networking</option>
            <option value="furniture" <?php echo $category_filter === 'furniture' ? 'selected' : ''; ?>>Furniture</option>
            <option value="security" <?php echo $category_filter === 'security' ? 'selected' : ''; ?>>Security</option>
            <option value="equipment" <?php echo $category_filter === 'equipment' ? 'selected' : ''; ?>>Equipment</option>
            <option value="other" <?php echo $category_filter === 'other' ? 'selected' : ''; ?>>Other</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="fas fa-filter"></i> Filter
        </button>
        <a href="requests.php" class="btn btn-outline btn-sm">
            <i class="fas fa-redo"></i> Reset
        </a>
    </form>
</div>

<div class="results-count">
    <p>Showing <?php echo count($requests); ?> request(s)</p>
</div>

<?php if (empty($requests)): ?>
<div class="empty-state">
    <i class="fas fa-search"></i>
    <h3>No results found</h3>
    <p>Try adjusting your filters or search terms.</p>
</div>
<?php else: ?>
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Reported By</th>
                <th>Issue Location</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $req): ?>
            <tr>
                <td><?php echo $req['id']; ?></td>
                <td class="title-cell"><?php echo htmlspecialchars(substr($req['title'], 0, 50)); ?></td>
                <td><?php echo htmlspecialchars($req['reporter_name']); ?></td>
                <td><?php echo htmlspecialchars($req['issue_location']); ?></td>
                <td><span class="badge badge-category"><?php echo ucfirst(str_replace('_', ' ', $req['category'])); ?></span></td>
                <td><span class="badge badge-priority-<?php echo $req['priority']; ?>"><?php echo ucfirst($req['priority']); ?></span></td>
                <td><span class="badge badge-<?php echo $req['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?></span></td>
                <td><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
                <td class="action-cell">
                    <a href="manage-request.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-primary" title="Manage">
                        <i class="fas fa-cog"></i>
                    </a>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <form method="POST" action="<?php echo APP_URL; ?>/api/delete-request.php" class="inline-form" 
                          onsubmit="return confirm('Are you sure you want to delete this request?');">
                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
