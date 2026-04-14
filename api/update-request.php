<?php
/**
 * Update Maintenance Request API
 * POST — update status, assign technician, add comment
 * Accessible by managers, admins, and technicians (with limited scope)
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['technician', 'manager', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit();
}

$request_id = intval($_POST['request_id'] ?? 0);
$new_status = $_POST['status'] ?? '';
$assigned_to = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
$comment = trim($_POST['comment'] ?? '');
$org_id = get_org_id();

if ($request_id <= 0) {
    $_SESSION['error_message'] = 'Invalid request ID.';
    header('Location: ' . APP_URL . '/admin/requests.php');
    exit();
}

$db = getDBConnection();

// Get current request (org-scoped)
$stmt = $db->prepare("SELECT * FROM maintenance_requests WHERE id = ? AND organization_id = ?");
$stmt->execute([$request_id, $org_id]);
$request = $stmt->fetch();

if (!$request) {
    $_SESSION['error_message'] = 'Request not found.';
    header('Location: ' . APP_URL . '/admin/requests.php');
    exit();
}

// Technicians can only update tasks assigned to them
if ($_SESSION['user_role'] === 'technician' && $request['assigned_to'] != $_SESSION['user_id']) {
    $_SESSION['error_message'] = 'You can only update tasks assigned to you.';
    header('Location: ' . APP_URL . '/technician/dashboard.php');
    exit();
}

$old_status = $request['status'];

// Build update query dynamically
$updates = [];
$params = [];

if (!empty($new_status) && $new_status !== $old_status) {
    $valid_statuses = ['pending', 'in_progress', 'resolved', 'closed'];
    if (in_array($new_status, $valid_statuses)) {
        $updates[] = "status = ?";
        $params[] = $new_status;
    }
}

// Only managers/admins can reassign
if ($assigned_to !== null && in_array($_SESSION['user_role'], ['manager', 'admin'])) {
    $updates[] = "assigned_to = ?";
    $params[] = $assigned_to;
}

if (!empty($updates)) {
    $params[] = $request_id;
    $sql = "UPDATE maintenance_requests SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
}

// Log the update
$log_stmt = $db->prepare("INSERT INTO request_updates (request_id, updated_by, old_status, new_status, comment) VALUES (?, ?, ?, ?, ?)");
$log_stmt->execute([
    $request_id,
    $_SESSION['user_id'],
    $old_status,
    !empty($new_status) ? $new_status : $old_status,
    $comment
]);

$_SESSION['success_message'] = 'Request updated successfully!';

// Redirect based on role
if ($_SESSION['user_role'] === 'technician') {
    header('Location: ' . APP_URL . '/technician/view-task.php?id=' . $request_id);
} else {
    header('Location: ' . APP_URL . '/admin/manage-request.php?id=' . $request_id);
}
exit();
?>
