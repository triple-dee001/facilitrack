<?php
/**
 * Shared Header Include
 * Contains navigation bar and common HTML head
 * Supports 4 roles: user, technician, manager, admin
 */
if (!isset($page_title)) $page_title = APP_NAME;
$user = current_user();
$user_role = $user ? $user['role'] : '';
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FaciliTrack — Digital Facility Maintenance Reporting System">
    <title><?php echo htmlspecialchars($page_title); ?> | <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body>
    <?php if ($user): ?>
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="<?php echo APP_URL; ?>" class="logo">
                <?php if ($user['logo_path']): ?>
                    <img src="<?php echo APP_URL . '/' . htmlspecialchars($user['logo_path']); ?>" alt="Logo" class="sidebar-org-logo">
                <?php else: ?>
                    <div class="logo-icon"><i class="fas fa-building"></i></div>
                <?php endif; ?>
                <span class="logo-text"><?php echo htmlspecialchars(strlen($user['org_name']) > 15 ? substr($user['org_name'],0,15).'...' : $user['org_name']); ?></span>
            </a>
            <button class="sidebar-close" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <?php if ($user_role === 'user'): ?>
                <a href="<?php echo APP_URL; ?>/tenant/dashboard.php" class="nav-item <?php echo ($current_page === 'dashboard.php' && $current_dir === 'tenant') ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo APP_URL; ?>/tenant/new-request.php" class="nav-item <?php echo $current_page === 'new-request.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span>Report Issue</span>
                </a>
                <a href="<?php echo APP_URL; ?>/tenant/my-requests.php" class="nav-item <?php echo $current_page === 'my-requests.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>My Reports</span>
                </a>

                <div class="nav-label">Settings</div>
                <a href="<?php echo APP_URL; ?>/profile.php" class="nav-item <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i>
                    <span>Profile Settings</span>
                </a>
            <?php elseif ($user_role === 'technician'): ?>
                <a href="<?php echo APP_URL; ?>/technician/dashboard.php" class="nav-item <?php echo ($current_page === 'dashboard.php' && $current_dir === 'technician') ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo APP_URL; ?>/technician/my-tasks.php" class="nav-item <?php echo $current_page === 'my-tasks.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i>
                    <span>My Tasks</span>
                </a>

                <div class="nav-label">Settings</div>
                <a href="<?php echo APP_URL; ?>/profile.php" class="nav-item <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i>
                    <span>Profile Settings</span>
                </a>
            <?php elseif ($user_role === 'superadmin'): ?>
                <div class="nav-label">Platform Overview</div>
                <a href="<?php echo APP_URL; ?>/superadmin/dashboard.php" class="nav-item <?php echo ($current_page === 'dashboard.php' && $current_dir === 'superadmin') ? 'active' : ''; ?>">
                    <i class="fas fa-globe"></i>
                    <span>Organizations</span>
                </a>
                
                <div class="nav-label">Settings</div>
                <a href="<?php echo APP_URL; ?>/profile.php" class="nav-item <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i>
                    <span>Profile Settings</span>
                </a>
            <?php else: /* admin/manager */ ?>
                <a href="<?php echo APP_URL; ?>/admin/dashboard.php" class="nav-item <?php echo ($current_page === 'dashboard.php' && $current_dir === 'admin') ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>

                <div class="nav-label">Maintenance</div>
                <a href="<?php echo APP_URL; ?>/admin/requests.php" class="nav-item <?php echo $current_page === 'requests.php' ? 'active' : ''; ?>">
                    <i class="fas fa-triangle-exclamation"></i>
                    <span>Issues</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/manage-request.php" class="nav-item <?php echo $current_page === 'manage-request.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Work Orders</span>
                </a>
                <a href="<?php echo APP_URL; ?>/admin/reports.php" class="nav-item <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports &amp; Analytics</span>
                </a>

                <?php if ($user_role === 'admin'): ?>
                <div class="nav-label">Organization</div>
                <a href="<?php echo APP_URL; ?>/admin/users.php" class="nav-item <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <?php endif; ?>
                
                <div class="nav-label">Settings</div>
                <a href="<?php echo APP_URL; ?>/profile.php" class="nav-item <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Profile & Settings</span>
                </a>
            <?php endif; ?>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <?php if ($user['profile_image']): ?>
                    <img src="<?php echo APP_URL . '/' . htmlspecialchars($user['profile_image']); ?>" alt="User" class="user-avatar-img">
                <?php else: ?>
                    <div class="user-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                <?php endif; ?>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                    <span class="user-role"><?php echo ucfirst($user_role); ?></span>
                </div>
            </div>
            <a href="<?php echo APP_URL; ?>/logout.php" class="nav-item logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sign Out</span>
            </a>
        </div>
    </aside>
    
    <!-- Main Content Area -->
    <main class="main-content" id="mainContent">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <div class="navbar-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="page-heading">
                    <h1 class="page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                    <?php if (isset($page_subtitle)): ?>
                    <p class="page-sub"><?php echo $page_subtitle; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="navbar-right">
                <div class="org-pill">
                    <?php if ($user['logo_path']): ?>
                        <img src="<?php echo APP_URL . '/' . htmlspecialchars($user['logo_path']); ?>" alt="Org" class="nav-org-img">
                    <?php else: ?>
                        <i class="fas fa-building"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($user['org_name']); ?>
                </div>
                <div class="nav-user-pill">
                    <?php if ($user['profile_image']): ?>
                        <img src="<?php echo APP_URL . '/' . htmlspecialchars($user['profile_image']); ?>" alt="Avatar" class="nav-avatar-img">
                    <?php else: ?>
                        <div class="nav-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($user['name']); ?></span>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="page-content">
    <?php else: ?>
    <!-- Public page (no sidebar) -->
    <main class="auth-main">
    <?php endif; ?>
