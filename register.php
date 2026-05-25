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
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="description" content="FaciliTrack — Register as a user">
<title>Register | FaciliTrack</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?php echo APP_URL; ?>/auth.css">
</head>
<body>
<div class="auth-wrap">

  <!-- LEFT — FORM -->
  <div class="auth-left">
    <a href="index.php" class="auth-brand">
      <div class="auth-brand-icon"><i class="fas fa-tools"></i></div>
      <span class="auth-brand-name">FaciliTrack</span>
    </a>

    <h1 class="auth-heading">Create Account</h1>
    <p class="auth-sub">Join your organization to report and track facility maintenance issues. You'll need the code provided by your admin.</p>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <i class="fas fa-exclamation-circle"></i>
      <ul><?php foreach ($errors as $err): ?><li><?php echo htmlspecialchars($err); ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="" id="registerForm">
      <div class="f-section"><i class="fas fa-key"></i> Organization</div>
      <div class="fg">
        <label>Organization Code <span class="req">*</span></label>
        <input type="text" id="org_code" name="org_code" placeholder="e.g. JUMI-A3B2C1 (from your admin)" required style="text-transform:uppercase" value="<?php echo htmlspecialchars($form_data['org_code'] ?? ''); ?>">
      </div>

      <div class="f-section"><i class="fas fa-user"></i> Personal Details</div>
      <div class="f-row">
        <div class="fg">
          <label>Full Name <span class="req">*</span></label>
          <input type="text" id="full_name" name="full_name" placeholder="Your full name" required value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>">
        </div>
        <div class="fg">
          <label>Email Address <span class="req">*</span></label>
          <input type="email" id="email" name="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
        </div>
      </div>
      <div class="f-row">
        <div class="fg">
          <label>Phone Number <span class="req">*</span></label>
          <input type="tel" id="phone" name="phone" placeholder="08012345678" required value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>">
        </div>
        <div class="fg">
          <label>Department / Unit</label>
          <input type="text" id="department" name="department" placeholder="e.g. Sales, Block A" value="<?php echo htmlspecialchars($form_data['department'] ?? ''); ?>">
        </div>
      </div>
      <div class="fg">
        <label>Your Location <span class="req">*</span></label>
        <input type="text" id="location" name="location" placeholder="e.g. Floor 5 Room 503, Office 204" required value="<?php echo htmlspecialchars($form_data['location'] ?? ''); ?>">
      </div>

      <div class="f-section"><i class="fas fa-lock"></i> Security</div>
      <div class="f-row">
        <div class="fg">
          <label>Password <span class="req">*</span></label>
          <div class="pw-wrap">
            <input type="password" id="password" name="password" placeholder="Min. 6 characters" required minlength="6">
            <button type="button" class="pw-eye" onclick="togglePw('password')"><i class="fas fa-eye"></i></button>
          </div>
        </div>
        <div class="fg">
          <label>Confirm Password <span class="req">*</span></label>
          <div class="pw-wrap">
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
            <button type="button" class="pw-eye" onclick="togglePw('confirm_password')"><i class="fas fa-eye"></i></button>
          </div>
        </div>
      </div>

      <button type="submit" class="btn-submit"><i class="fas fa-user-plus"></i> Create Account</button>
    </form>

    <div class="auth-links">
      Already have an account? <a href="index.php">Sign in</a><br>
      New organization? <a href="register-organization.php">Register your facility</a>
    </div>
  </div>

  <!-- RIGHT — PREVIEW -->
  <div class="auth-right">
    <div class="preview-card">
      <img src="<?php echo APP_URL; ?>/assets/images/preview-dashboard.png" alt="FaciliTrack Dashboard Preview">
      <div class="preview-label">Your Dashboard After Signing In</div>
    </div>
  </div>

</div>
<script>
function togglePw(id){var i=document.getElementById(id);i.type=i.type==='password'?'text':'password'}
</script>
</body>
</html>

