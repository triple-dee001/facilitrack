<?php
/**
 * Shared Header Include
 * Contains navigation bar and common HTML head
 * Supports 4 roles: user, technician, manager, admin
 */
if (!isset($page_title)) $page_title = APP_NAME;
$user = current_user();
$user_role = $user ? $user['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FaciliTrack — Digital Facility Maintenance Reporting System">
    <title><?php echo htmlspecialchars($page_title); ?> | <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body>
    <?php if ($user): ?>
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-tools"></i>
                <span><?php echo APP_NAME; ?></span>
            </div>
            <button class="sidebar-close" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Organization Badge -->
        <div class="org-badge">
            <i class="fas fa-building"></i>
            <span><?php echo htmlspecialchars($user['org_name']); ?></span>
        </div>
        
        <nav class="sidebar-nav">
            <?php if ($user_role === 'user'): ?>
                <a href="<?php echo APP_URL; ?>/tenant/dashboard.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'tenant') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo APP_URL; ?>/tenant/new-request.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'new-request.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span>Report Issue</span>
                </a>
                <a href="<?php echo APP_URL; ?>/tenant/my-requests.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'my-requests.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>My Reports</span>
                </a>
            <?php elseif ($user_role === 'technician'): ?>
                <a href="<?php echo APP_URL; ?>/technician/dashboard.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'technician') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo APP_URL; ?>/technician/my-tasks.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'my-tasks.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i>
                    <span>My Tasks</span>
                </a>
            <?php else: ?>
                <a href="<?php echo APP_URL; ?>/admin/dashboard.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'admin') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/requests.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'requests.php' ? 'active' : ''; ?>">
                    <i class="fas fa-wrench"></i>
                    <span>All Requests</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/manage-request.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'manage-request.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i>
                    <span>Manage Tasks</span>
                </a>
                <?php if ($user_role === 'admin'): ?>
                <a href="<?php echo APP_URL; ?>/admin/users.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                    <span class="user-role"><?php echo ucfirst($user_role); ?></span>
                </div>
            </div>
            <a href="<?php echo APP_URL; ?>/logout.php" class="nav-item logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>
    
    <!-- Main Content Area -->
    <main class="main-content" id="mainContent">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title"><?php echo htmlspecialchars($page_title); ?></h1>
            <div class="navbar-right">
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="page-content">
    <?php else: ?>
    <!-- Public page (no sidebar) -->
    <main class="auth-main">
    <?php endif; ?>
