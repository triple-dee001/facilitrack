<?php
/**
 * Authentication Module
 * Handles login, registration, session management, and access control
 * Supports multi-organization with 4 roles: user, technician, manager, admin
 */

session_start();

require_once __DIR__ . '/../config/database.php';

/**
 * Register a new organization and its admin
 */
function register_organization($org_name, $org_type, $org_address, $admin_name, $admin_email, $admin_password, $admin_phone) {
    $db = getDBConnection();
    
    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$admin_email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already registered.'];
    }
    
    // Generate unique org code
    $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $org_name), 0, 4));
    $suffix = strtoupper(bin2hex(random_bytes(3)));
    $org_code = $prefix . '-' . $suffix;
    
    // Ensure uniqueness
    $stmt = $db->prepare("SELECT id FROM organizations WHERE org_code = ?");
    $stmt->execute([$org_code]);
    while ($stmt->fetch()) {
        $suffix = strtoupper(bin2hex(random_bytes(3)));
        $org_code = $prefix . '-' . $suffix;
        $stmt->execute([$org_code]);
    }
    
    try {
        $db->beginTransaction();
        
        // Create organization
        $stmt = $db->prepare("INSERT INTO organizations (name, type, address, org_code) VALUES (?, ?, ?, ?)");
        $stmt->execute([$org_name, $org_type, $org_address, $org_code]);
        $org_id = $db->lastInsertId();
        
        // Create admin user (auto-approved)
        $password_hash = password_hash($admin_password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (organization_id, full_name, email, password_hash, phone, department, location, role, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, 'admin', 1)");
        $stmt->execute([$org_id, $admin_name, $admin_email, $password_hash, $admin_phone, 'Administration', 'Admin Office']);
        $admin_id = $db->lastInsertId();
        
        // Link org to admin
        $stmt = $db->prepare("UPDATE organizations SET created_by = ? WHERE id = ?");
        $stmt->execute([$admin_id, $org_id]);
        
        $db->commit();
        
        return ['success' => true, 'message' => 'Organization registered successfully.', 'org_code' => $org_code];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

/**
 * Register a new user (self-registration with org code)
 */
function register_user($full_name, $email, $password, $phone, $department, $location, $org_code) {
    $db = getDBConnection();
    
    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already registered.'];
    }
    
    // Validate org code
    $stmt = $db->prepare("SELECT id FROM organizations WHERE org_code = ? AND is_active = 1");
    $stmt->execute([$org_code]);
    $org = $stmt->fetch();
    if (!$org) {
        return ['success' => false, 'message' => 'Invalid organization code. Please check with your administrator.'];
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $db->prepare("INSERT INTO users (organization_id, full_name, email, password_hash, phone, department, location, role, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, 'user', 0)");
    $stmt->execute([$org['id'], $full_name, $email, $password_hash, $phone, $department, $location]);
    
    return ['success' => true, 'message' => 'Registration submitted! Your account is pending admin approval.'];
}

/**
 * Admin adds a user manually (can set any role)
 */
function admin_add_user($full_name, $email, $password, $phone, $department, $location, $role, $org_id) {
    $db = getDBConnection();
    
    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already registered.'];
    }
    
    $valid_roles = ['user', 'technician', 'manager', 'admin'];
    if (!in_array($role, $valid_roles)) {
        return ['success' => false, 'message' => 'Invalid role specified.'];
    }
    
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $db->prepare("INSERT INTO users (organization_id, full_name, email, password_hash, phone, department, location, role, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([$org_id, $full_name, $email, $password_hash, $phone, $department, $location, $role]);
    
    return ['success' => true, 'message' => 'User added successfully.'];
}

/**
 * Login user
 */
function login_user($email, $password) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("SELECT u.*, o.name as org_name, o.org_code 
                           FROM users u 
                           JOIN organizations o ON u.organization_id = o.id 
                           WHERE u.email = ? AND u.is_active = 1 AND o.is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }
    
    // Check if user is approved
    if (!$user['is_approved']) {
        return ['success' => false, 'message' => 'Your account is pending admin approval. Please contact your administrator.'];
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['organization_id'] = $user['organization_id'];
    $_SESSION['org_name'] = $user['org_name'];
    $_SESSION['org_code'] = $user['org_code'];
    
    return ['success' => true, 'message' => 'Login successful.', 'role' => $user['role']];
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current logged-in user info
 */
function current_user() {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
        'organization_id' => $_SESSION['organization_id'],
        'org_name' => $_SESSION['org_name'],
        'org_code' => $_SESSION['org_code']
    ];
}

/**
 * Require user to be logged in — redirects to login if not
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . APP_URL . '/index.php?error=Please login first');
        exit();
    }
}

/**
 * Require specific role — redirects if unauthorized
 */
function require_role($allowed_roles) {
    require_login();
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    if (!in_array($_SESSION['user_role'], $allowed_roles)) {
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
}

/**
 * Get current user's organization ID
 */
function get_org_id() {
    return $_SESSION['organization_id'] ?? 0;
}

/**
 * Logout user
 */
function logout_user() {
    session_destroy();
    header('Location: ' . APP_URL . '/index.php');
    exit();
}
?>
