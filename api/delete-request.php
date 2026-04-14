<?php
/**
 * Delete Maintenance Request API
 * POST — admin-only request deletion (org-scoped)
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit();
}

$request_id = intval($_POST['request_id'] ?? 0);
$org_id = get_org_id();

if ($request_id <= 0) {
    $_SESSION['error_message'] = 'Invalid request ID.';
    header('Location: ' . APP_URL . '/admin/requests.php');
    exit();
}

$db = getDBConnection();

// Get image path to delete file (org-scoped)
$stmt = $db->prepare("SELECT image_path FROM maintenance_requests WHERE id = ? AND organization_id = ?");
$stmt->execute([$request_id, $org_id]);
$request = $stmt->fetch();

if (!$request) {
    $_SESSION['error_message'] = 'Request not found.';
    header('Location: ' . APP_URL . '/admin/requests.php');
    exit();
}

// Delete the image file if exists
if (!empty($request['image_path'])) {
    $file_path = __DIR__ . '/../' . $request['image_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// Delete from database (cascades to request_updates)
$stmt = $db->prepare("DELETE FROM maintenance_requests WHERE id = ? AND organization_id = ?");
$stmt->execute([$request_id, $org_id]);

$_SESSION['success_message'] = 'Request deleted successfully.';
header('Location: ' . APP_URL . '/admin/requests.php');
exit();
?>
