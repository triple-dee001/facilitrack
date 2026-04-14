<?php
/**
 * User Management — admin only
 * View, update roles, deactivate users, add new users
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['admin']);

$page_title = 'Manage Users';
$db = getDBConnection();
$org_id = get_org_id();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $target_id = intval($_POST['user_id'] ?? 0);
    
    if ($_POST['action'] === 'add_user') {
        // Add new user manually
        $result = admin_add_user(
            trim($_POST['full_name'] ?? ''),
            trim($_POST['email'] ?? ''),
            $_POST['password'] ?? '',
            trim($_POST['phone'] ?? ''),
            trim($_POST['department'] ?? ''),
            trim($_POST['location'] ?? ''),
            $_POST['new_role'] ?? 'user',
            $org_id
        );
        $_SESSION[$result['success'] ? 'success_message' : 'error_message'] = $result['message'];
    } elseif ($_POST['action'] === 'approve_user') {
        $stmt = $db->prepare("UPDATE users SET is_approved = 1 WHERE id = ? AND organization_id = ?");
        $stmt->execute([$target_id, $org_id]);
        $_SESSION['success_message'] = 'User approved successfully.';
    } elseif ($_POST['action'] === 'reject_user') {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND organization_id = ? AND is_approved = 0");
        $stmt->execute([$target_id, $org_id]);
        $_SESSION['success_message'] = 'Registration rejected and removed.';
    } elseif ($target_id === $_SESSION['user_id']) {
        $_SESSION['error_message'] = 'You cannot modify your own account.';
    } elseif ($_POST['action'] === 'update_role') {
        $new_role = $_POST['new_role'] ?? '';
        $valid_roles = ['user', 'technician', 'manager', 'admin'];
        if (in_array($new_role, $valid_roles)) {
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ? AND organization_id = ?");
            $stmt->execute([$new_role, $target_id, $org_id]);
            $_SESSION['success_message'] = 'User role updated successfully.';
        }
    } elseif ($_POST['action'] === 'toggle_active') {
        $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND organization_id = ?");
        $stmt->execute([$target_id, $org_id]);
        $_SESSION['success_message'] = 'User status updated.';
    }
    
    header('Location: ' . APP_URL . '/admin/users.php');
    exit();
}

// Get all approved users in this org
$stmt = $db->prepare("SELECT * FROM users WHERE organization_id = ? AND is_approved = 1 ORDER BY created_at DESC");
$stmt->execute([$org_id]);
$users = $stmt->fetchAll();

// Get pending users
$stmt = $db->prepare("SELECT * FROM users WHERE organization_id = ? AND is_approved = 0 ORDER BY created_at DESC");
$stmt->execute([$org_id]);
$pending_users = $stmt->fetchAll();

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

<!-- Add User Form -->
<div class="detail-card" style="margin-bottom: 20px;">
    <h2><i class="fas fa-user-plus"></i> Add New User</h2>
    <form method="POST" action="" class="add-user-form">
        <input type="hidden" name="action" value="add_user">
        <div class="form-row">
            <div class="form-group">
                <input type="text" name="full_name" placeholder="Full Name" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Email Address" required>
            </div>
            <div class="form-group">
                <input type="tel" name="phone" placeholder="Phone Number">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <input type="text" name="department" placeholder="Department (optional)">
            </div>
            <div class="form-group">
                <input type="text" name="location" placeholder="Location (e.g. Office 204)">
            </div>
            <div class="form-group">
                <select name="new_role">
                    <option value="user">User</option>
                    <option value="technician">Technician</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required minlength="6">
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Add User
        </button>
    </form>
</div>

<?php if (!empty($pending_users)): ?>
<div class="detail-card" style="margin-bottom: 20px; border-left: 4px solid #F59E0B;">
    <h2><i class="fas fa-user-clock" style="color: #F59E0B;"></i> Pending Registrations (<?php echo count($pending_users); ?>)</h2>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Department</th>
                    <th>Location</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_users as $pu): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar-sm" style="background: #F59E0B;"><?php echo strtoupper(substr($pu['full_name'], 0, 1)); ?></div>
                            <?php echo htmlspecialchars($pu['full_name']); ?>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($pu['email']); ?></td>
                    <td><?php echo htmlspecialchars($pu['phone']); ?></td>
                    <td><?php echo htmlspecialchars($pu['department'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($pu['location']); ?></td>
                    <td><?php echo date('M d, Y h:i A', strtotime($pu['created_at'])); ?></td>
                    <td class="action-cell">
                        <form method="POST" action="" class="inline-form">
                            <input type="hidden" name="action" value="approve_user">
                            <input type="hidden" name="user_id" value="<?php echo $pu['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this user?');">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        </form>
                        <form method="POST" action="" class="inline-form">
                            <input type="hidden" name="action" value="reject_user">
                            <input type="hidden" name="user_id" value="<?php echo $pu['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject and delete this registration?');">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="section-header">
    <h2><i class="fas fa-users"></i> All Users (<?php echo count($users); ?>)</h2>
</div>

<div class="table-responsive">
    <table class="data-table" id="usersTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Department</th>
                <th>Location</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr class="<?php echo !$u['is_active'] ? 'row-inactive' : ''; ?>">
                <td><?php echo $u['id']; ?></td>
                <td>
                    <div class="user-cell">
                        <div class="user-avatar-sm"><?php echo strtoupper(substr($u['full_name'], 0, 1)); ?></div>
                        <?php echo htmlspecialchars($u['full_name']); ?>
                    </div>
                </td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><?php echo htmlspecialchars($u['phone']); ?></td>
                <td><?php echo htmlspecialchars($u['department'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($u['location']); ?></td>
                <td>
                    <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                    <form method="POST" action="" class="inline-form role-form">
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                        <select name="new_role" onchange="this.form.submit()" class="role-select role-<?php echo $u['role']; ?>">
                            <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="technician" <?php echo $u['role'] === 'technician' ? 'selected' : ''; ?>>Technician</option>
                            <option value="manager" <?php echo $u['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                            <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </form>
                    <?php else: ?>
                    <span class="badge badge-role"><?php echo ucfirst($u['role']); ?> (You)</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?php echo $u['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                        <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </td>
                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                <td>
                    <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                    <form method="POST" action="" class="inline-form">
                        <input type="hidden" name="action" value="toggle_active">
                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                        <button type="submit" class="btn btn-sm <?php echo $u['is_active'] ? 'btn-warning' : 'btn-success'; ?>"
                                onclick="return confirm('<?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?> this user?');">
                            <i class="fas <?php echo $u['is_active'] ? 'fa-ban' : 'fa-check'; ?>"></i>
                            <?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
