<?php
/**
 * Submit Maintenance Request API
 * POST — creates a new maintenance request
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['user']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/tenant/new-request.php');
    exit();
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = $_POST['category'] ?? 'other';
$priority = $_POST['priority'] ?? 'medium';
$issue_location = trim($_POST['issue_location'] ?? '');
$user_id = $_SESSION['user_id'];
$org_id = get_org_id();

// Validation
$errors = [];
if (empty($title)) $errors[] = 'Title is required.';
if (empty($description)) $errors[] = 'Description is required.';
if (empty($issue_location)) $errors[] = 'Issue location is required.';
if (strlen($title) > 200) $errors[] = 'Title must be under 200 characters.';
if (strlen($description) > 2000) $errors[] = 'Description must be under 2000 characters.';

$valid_categories = ['plumbing', 'electrical', 'structural', 'cleaning', 'pest_control', 'networking', 'furniture', 'security', 'equipment', 'other'];
if (!in_array($category, $valid_categories)) $errors[] = 'Invalid category.';

$valid_priorities = ['low', 'medium', 'high', 'critical'];
if (!in_array($priority, $valid_priorities)) $errors[] = 'Invalid priority.';

// Handle image upload
$image_path = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowed_types)) {
        $errors[] = 'Only JPEG, PNG, GIF, and WebP images are allowed.';
    } elseif ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = 'Image must be under 5MB.';
    } else {
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'req_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = UPLOAD_DIR . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $image_path = 'uploads/' . $filename;
        } else {
            $errors[] = 'Failed to upload image.';
        }
    }
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . APP_URL . '/tenant/new-request.php');
    exit();
}

// Insert into database
$db = getDBConnection();
$stmt = $db->prepare("INSERT INTO maintenance_requests (organization_id, user_id, title, description, category, priority, issue_location, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$org_id, $user_id, $title, $description, $category, $priority, $issue_location, $image_path]);

$_SESSION['success_message'] = 'Maintenance request submitted successfully!';
header('Location: ' . APP_URL . '/tenant/dashboard.php');
exit();
?>
