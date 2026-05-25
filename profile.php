<?php
/**
 * Profile & Settings Page
 * Allows users to update their profile and (if admin) organization settings
 */
require_once __DIR__ . '/includes/auth.php';
require_login();

$page_title = 'Profile & Settings';
$user = current_user();
$db = getDBConnection();
$errors = [];
$success = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }

    // Handle Profile Image Upload
    $profile_image_path = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_name = 'user_' . $user['id'] . '_' . time() . '.' . $ext;
            $dest = __DIR__ . '/uploads/profiles/' . $new_name;
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
                $profile_image_path = 'uploads/profiles/' . $new_name;
            } else {
                $errors[] = 'Failed to upload profile image.';
            }
        } else {
            $errors[] = 'Invalid image format for profile.';
        }
    }

    // Handle Org Logo Upload (Admin Only)
    $org_logo_path = $user['logo_path'];
    $org_name = $user['org_name'];
    
    if ($user['role'] === 'admin') {
        $org_name = trim($_POST['org_name'] ?? $user['org_name']);
        if (empty($org_name)) $errors[] = 'Organization name is required.';
        
        if (isset($_FILES['org_logo']) && $_FILES['org_logo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['org_logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_name = 'org_' . $user['organization_id'] . '_' . time() . '.' . $ext;
                $dest = __DIR__ . '/uploads/logos/' . $new_name;
                if (move_uploaded_file($_FILES['org_logo']['tmp_name'], $dest)) {
                    $org_logo_path = 'uploads/logos/' . $new_name;
                } else {
                    $errors[] = 'Failed to upload organization logo.';
                }
            } else {
                $errors[] = 'Invalid image format for logo.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Update User
            $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, profile_image = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $profile_image_path, $user['id']]);
            
            // Update Org (if admin)
            if ($user['role'] === 'admin') {
                $stmt = $db->prepare("UPDATE organizations SET name = ?, logo_path = ? WHERE id = ?");
                $stmt->execute([$org_name, $org_logo_path, $user['organization_id']]);
            }
            
            $db->commit();
            
            // Update Session Data
            $_SESSION['user_name'] = $full_name;
            $_SESSION['profile_image'] = $profile_image_path;
            if ($user['role'] === 'admin') {
                $_SESSION['org_name'] = $org_name;
                $_SESSION['logo_path'] = $org_logo_path;
            }
            
            $success = 'Settings updated successfully.';
            // Refresh user variable
            $user = current_user();
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch latest user data for form population
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$user_data = $stmt->fetch();

require_once __DIR__ . '/includes/header.php';
?>

<div class="form-container">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <ul>
                <?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-card">
            <div class="form-card-header">
                <h2><i class="fas fa-user-circle"></i> Personal Profile</h2>
                <p>Update your personal information and avatar.</p>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Profile Image</label>
                    <div style="display:flex; align-items:center; gap:16px; margin-top:8px;">
                        <?php if ($user_data['profile_image']): ?>
                            <img src="<?php echo APP_URL . '/' . htmlspecialchars($user_data['profile_image']); ?>" alt="Current Avatar" style="width:64px;height:64px;border-radius:12px;object-fit:cover;border:1px solid #E5E7EB;">
                        <?php else: ?>
                            <div style="width:64px;height:64px;border-radius:12px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;">
                                <?php echo strtoupper(substr($user_data['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="profile_image" accept="image/*" style="font-size:13px;">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Full Name <span class="req">*</span></label>
                    <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($user_data['full_name']); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled style="background:#F3F4F6; color:#9CA3AF;">
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <?php if ($user['role'] === 'admin'): ?>
        <div class="form-card" style="margin-top:24px;">
            <div class="form-card-header">
                <h2><i class="fas fa-building"></i> Organization Settings</h2>
                <p>Update the global settings and logo for your organization.</p>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Company Logo</label>
                    <div style="display:flex; align-items:center; gap:16px; margin-top:8px;">
                        <?php if ($user['logo_path']): ?>
                            <img src="<?php echo APP_URL . '/' . htmlspecialchars($user['logo_path']); ?>" alt="Current Logo" style="height:48px;max-width:120px;object-fit:contain;border:1px solid #E5E7EB;padding:4px;border-radius:6px;background:#fff;">
                        <?php else: ?>
                            <div style="width:48px;height:48px;border-radius:6px;background:var(--gray-100);color:var(--gray-500);display:flex;align-items:center;justify-content:center;font-size:20px;">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="org_logo" accept="image/*" style="font-size:13px;">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="org_name">Organization Name <span class="req">*</span></label>
                    <input type="text" id="org_name" name="org_name" required value="<?php echo htmlspecialchars($user['org_name']); ?>">
                </div>
                <div class="form-group">
                    <label>Organization Code</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['org_code']); ?>" disabled style="background:#F3F4F6; color:#9CA3AF; letter-spacing:1px; font-weight:600;">
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
