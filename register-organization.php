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
    $org_size = $_POST['org_size'] ?? '1-10';
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
    $valid_sizes = ['1-10', '11-100', '101-250', '251-1000', '1000+'];
    if (!in_array($org_size, $valid_sizes)) $errors[] = 'Please select organization size.';
    
    if (empty($errors)) {
        $result = register_organization($org_name, $org_type, $org_address, $org_size, $admin_name, $admin_email, $password, $admin_phone);
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
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="description" content="FaciliTrack — Register your organization">
<title>Register Organization | FaciliTrack</title>
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

    <h1 class="auth-heading">Register Organization</h1>
    <p class="auth-sub">Set up FaciliTrack for your facility. You'll be the admin — you can invite users after registration.</p>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <i class="fas fa-exclamation-circle"></i>
      <ul><?php foreach ($errors as $err): ?><li><?php echo htmlspecialchars($err); ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="" id="orgRegisterForm">
      <div class="f-section"><i class="fas fa-building"></i> Organization Details</div>
      <div class="f-row">
        <div class="fg">
          <label>Organization Name <span class="req">*</span></label>
          <input type="text" id="org_name" name="org_name" placeholder="e.g. Jumia Nigeria, University of Lagos" required value="<?php echo htmlspecialchars($form_data['org_name'] ?? ''); ?>">
        </div>
        <div class="fg">
          <label>Facility Type <span class="req">*</span></label>
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
      <div class="fg">
        <label>Address <span class="req">*</span></label>
        <input type="text" id="org_address" name="org_address" placeholder="e.g. 15 Marina Street, Lagos Island, Lagos" required value="<?php echo htmlspecialchars($form_data['org_address'] ?? ''); ?>">
      </div>

      <div class="fg">
        <label>How many people work in your organization?</label>
        <div class="size-pills">
          <?php $sel = $form_data['org_size'] ?? '1-10'; ?>
          <label class="pill<?php echo $sel==='1-10'?' active':''; ?>"><input type="radio" name="org_size" value="1-10" <?php echo $sel==='1-10'?'checked':''; ?>> 1-10</label>
          <label class="pill<?php echo $sel==='11-100'?' active':''; ?>"><input type="radio" name="org_size" value="11-100" <?php echo $sel==='11-100'?'checked':''; ?>> 11-100</label>
          <label class="pill<?php echo $sel==='101-250'?' active':''; ?>"><input type="radio" name="org_size" value="101-250" <?php echo $sel==='101-250'?'checked':''; ?>> 101-250</label>
          <label class="pill<?php echo $sel==='251-1000'?' active':''; ?>"><input type="radio" name="org_size" value="251-1000" <?php echo $sel==='251-1000'?'checked':''; ?>> 251-1000</label>
          <label class="pill<?php echo $sel==='1000+'?' active':''; ?>"><input type="radio" name="org_size" value="1000+" <?php echo $sel==='1000+'?'checked':''; ?>> 1000+</label>
        </div>
      </div>

      <div class="f-section"><i class="fas fa-user-shield"></i> Admin Account</div>
      <div class="f-row">
        <div class="fg">
          <label>Admin Full Name <span class="req">*</span></label>
          <input type="text" id="admin_name" name="admin_name" placeholder="Your full name" required value="<?php echo htmlspecialchars($form_data['admin_name'] ?? ''); ?>">
        </div>
        <div class="fg">
          <label>Admin Email <span class="req">*</span></label>
          <input type="email" id="admin_email" name="admin_email" placeholder="admin@yourorg.com" required value="<?php echo htmlspecialchars($form_data['admin_email'] ?? ''); ?>">
        </div>
      </div>
      <div class="fg">
        <label>Admin Phone <span class="req">*</span></label>
        <input type="tel" id="admin_phone" name="admin_phone" placeholder="08012345678" required value="<?php echo htmlspecialchars($form_data['admin_phone'] ?? ''); ?>">
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

      <button type="submit" class="btn-submit"><i class="fas fa-building"></i> Register Organization</button>
    </form>

    <div class="auth-links">
      Already have an account? <a href="index.php">Sign in</a><br>
      Want to join an existing organization? <a href="register.php">Register as a user</a>
    </div>
  </div>

  <!-- RIGHT — PREVIEW -->
  <div class="auth-right">
    <div class="preview-card">
      <img src="<?php echo APP_URL; ?>/assets/images/preview-dashboard.png" alt="FaciliTrack Dashboard Preview">
      <div class="preview-label">Your Admin Dashboard</div>
    </div>
  </div>

</div>
<script>
function togglePw(id){var i=document.getElementById(id);i.type=i.type==='password'?'text':'password'}
</script>
</body>
</html>

