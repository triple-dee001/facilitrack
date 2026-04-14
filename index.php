<?php
/**
 * Login Page — index.php
 * Landing page of FaciliTrack
 */
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    $role = $_SESSION['user_role'];
    if ($role === 'user') {
        header('Location: ' . APP_URL . '/tenant/dashboard.php');
    } elseif ($role === 'technician') {
        header('Location: ' . APP_URL . '/technician/dashboard.php');
    } else {
        header('Location: ' . APP_URL . '/admin/dashboard.php');
    }
    exit();
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $result = login_user($email, $password);
        if ($result['success']) {
            if ($result['role'] === 'user') {
                header('Location: ' . APP_URL . '/tenant/dashboard.php');
            } elseif ($result['role'] === 'technician') {
                header('Location: ' . APP_URL . '/technician/dashboard.php');
            } else {
                header('Location: ' . APP_URL . '/admin/dashboard.php');
            }
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

// Check for URL error param
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

$success = '';
if (isset($_GET['registered'])) {
    $success = 'Registration submitted! Your account is pending admin approval. You will be able to login once approved.';
}
if (isset($_GET['org_registered'])) {
    $success = 'Organization registered! Please login with your admin credentials.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FaciliTrack — Login to the Digital Facility Maintenance Reporting Platform">
    <title>Login | FaciliTrack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-tools"></i>
                </div>
                <h1>FaciliTrack</h1>
                <p class="auth-subtitle">Centralized Facility Maintenance Reporting Platform</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form" id="loginForm">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p class="mt-1">New organization? <a href="register-organization.php">Register your facility</a></p>
            </div>
            

        </div>
    </div>
    
    <script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body>
</html>
