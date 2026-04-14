<?php
/**
 * Organization Registration Page
 * Allows a new organization to sign up for FaciliTrack
 */
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect
if (is_logged_in()) {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit();
}

$errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $org_name = trim($_POST['org_name'] ?? '');
    $org_type = $_POST['org_type'] ?? 'other';
    $org_address = trim($_POST['org_address'] ?? '');
    $admin_name = trim($_POST['admin_name'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_phone = trim($_POST['admin_phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $form_data = $_POST;
    
    // Validation
    if (empty($org_name)) $errors[] = 'Organization name is required.';
    if (empty($org_address)) $errors[] = 'Organization address is required.';
    if (empty($admin_name)) $errors[] = 'Admin full name is required.';
    if (empty($admin_email) || !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid admin email is required.';
    if (empty($admin_phone)) $errors[] = 'Admin phone number is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';
    
    $valid_types = ['office', 'campus', 'residential', 'school', 'hospital', 'other'];
    if (!in_array($org_type, $valid_types)) $errors[] = 'Invalid organization type.';
    
    if (empty($errors)) {
        $result = register_organization($org_name, $org_type, $org_address, $admin_name, $admin_email, $password, $admin_phone);
        if ($result['success']) {
            header('Location: ' . APP_URL . '/index.php?org_registered=1');
            exit();
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FaciliTrack — Register your organization">
    <title>Register Organization | FaciliTrack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card auth-card-wide">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-building"></i>
                </div>
                <h1>Register Organization</h1>
                <p class="auth-subtitle">Set up FaciliTrack for your facility — office, campus, school, or residential building</p>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form" id="orgRegisterForm">
                <h3 style="color: var(--primary-deep); font-size: 15px; margin-bottom: -8px;">
                    <i class="fas fa-building" style="color: var(--primary);"></i> Organization Details
                </h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="org_name">
                            <i class="fas fa-building"></i>
                            Organization Name <span class="required">*</span>
                        </label>
                        <input type="text" id="org_name" name="org_name" placeholder="e.g. Jumia Nigeria, University of Lagos" required
                               value="<?php echo htmlspecialchars($form_data['org_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="org_type">
                            <i class="fas fa-th-large"></i>
                            Facility Type <span class="required">*</span>
                        </label>
                        <select id="org_type" name="org_type" required>
                            <option value="office" <?php echo ($form_data['org_type'] ?? '') === 'office' ? 'selected' : ''; ?>>🏢 Office / Corporate</option>
                            <option value="campus" <?php echo ($form_data['org_type'] ?? '') === 'campus' ? 'selected' : ''; ?>>🎓 University / Polytechnic</option>
                            <option value="school" <?php echo ($form_data['org_type'] ?? '') === 'school' ? 'selected' : ''; ?>>🏫 School (Primary/Secondary)</option>
                            <option value="residential" <?php echo ($form_data['org_type'] ?? '') === 'residential' ? 'selected' : ''; ?>>🏠 Residential / Hostel</option>
                            <option value="hospital" <?php echo ($form_data['org_type'] ?? '') === 'hospital' ? 'selected' : ''; ?>>🏥 Hospital / Clinic</option>
                            <option value="other" <?php echo ($form_data['org_type'] ?? '') === 'other' ? 'selected' : ''; ?>>📋 Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="org_address">
                        <i class="fas fa-map-marker-alt"></i>
                        Address <span class="required">*</span>
                    </label>
                    <input type="text" id="org_address" name="org_address" placeholder="e.g. 15 Marina Street, Lagos Island, Lagos" required
                           value="<?php echo htmlspecialchars($form_data['org_address'] ?? ''); ?>">
                </div>
                
                <h3 style="color: var(--primary-deep); font-size: 15px; margin-top: 8px; margin-bottom: -8px;">
                    <i class="fas fa-user-shield" style="color: var(--primary);"></i> Admin Account
                </h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="admin_name">
                            <i class="fas fa-user"></i>
                            Admin Full Name <span class="required">*</span>
                        </label>
                        <input type="text" id="admin_name" name="admin_name" placeholder="Enter admin full name" required
                               value="<?php echo htmlspecialchars($form_data['admin_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">
                            <i class="fas fa-envelope"></i>
                            Admin Email <span class="required">*</span>
                        </label>
                        <input type="email" id="admin_email" name="admin_email" placeholder="Enter admin email" required
                               value="<?php echo htmlspecialchars($form_data['admin_email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="admin_phone">
                            <i class="fas fa-phone"></i>
                            Admin Phone <span class="required">*</span>
                        </label>
                        <input type="tel" id="admin_phone" name="admin_phone" placeholder="e.g. 08012345678" required
                               value="<?php echo htmlspecialchars($form_data['admin_phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group"></div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Password <span class="required">*</span>
                        </label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" placeholder="Minimum 6 characters" required minlength="6">
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i>
                            Confirm Password <span class="required">*</span>
                        </label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-building"></i>
                    Register Organization
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="index.php">Login here</a></p>
                <p class="mt-1">Want to join an existing organization? <a href="register.php">Register as a user</a></p>
            </div>
        </div>
    </div>
    
    <script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body>
</html>
