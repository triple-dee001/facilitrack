<?php
/**
 * User Registration Page
 * Users join an existing organization using an organization code
 */
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect
if (is_logged_in()) {
    header('Location: ' . APP_URL . '/tenant/dashboard.php');
    exit();
}

$errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $org_code = strtoupper(trim($_POST['org_code'] ?? ''));
    $department = trim($_POST['department'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $form_data = $_POST;
    
    // Validation
    if (empty($full_name)) $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (empty($phone)) $errors[] = 'Phone number is required.';
    if (empty($org_code)) $errors[] = 'Organization code is required.';
    if (empty($location)) $errors[] = 'Location is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';
    
    if (empty($errors)) {
        $result = register_user($full_name, $email, $password, $phone, $department, $location, $org_code);
        if ($result['success']) {
            header('Location: ' . APP_URL . '/index.php?registered=1');
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
    <meta name="description" content="FaciliTrack — Register as a user">
    <title>Register | FaciliTrack</title>
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
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1>Create Account</h1>
                <p class="auth-subtitle">Join your organization to report and track facility maintenance issues</p>
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
            
            <form method="POST" action="" class="auth-form" id="registerForm">
                <div class="form-group">
                    <label for="org_code">
                        <i class="fas fa-key"></i>
                        Organization Code <span class="required">*</span>
                    </label>
                    <input type="text" id="org_code" name="org_code" placeholder="e.g. JUMI-A3B2C1 (provided by your admin)" required
                           style="text-transform: uppercase;"
                           value="<?php echo htmlspecialchars($form_data['org_code'] ?? ''); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">
                            <i class="fas fa-user"></i>
                            Full Name <span class="required">*</span>
                        </label>
                        <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required
                               value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Email Address <span class="required">*</span>
                        </label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required
                               value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">
                            <i class="fas fa-phone"></i>
                            Phone Number <span class="required">*</span>
                        </label>
                        <input type="tel" id="phone" name="phone" placeholder="e.g. 08012345678" required
                               value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="department">
                            <i class="fas fa-sitemap"></i>
                            Department / Unit
                        </label>
                        <input type="text" id="department" name="department" placeholder="e.g. Sales, Computer Science, Block A"
                               value="<?php echo htmlspecialchars($form_data['department'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="location">
                        <i class="fas fa-map-marker-alt"></i>
                        Your Location <span class="required">*</span>
                    </label>
                    <input type="text" id="location" name="location" placeholder="e.g. Floor 5 Room 503, Block A Room 12, Office 204" required
                           value="<?php echo htmlspecialchars($form_data['location'] ?? ''); ?>">
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
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="index.php">Login here</a></p>
                <p class="mt-1">New organization? <a href="register-organization.php">Register your facility</a></p>
            </div>
        </div>
    </div>
    
    <script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body>
</html>
