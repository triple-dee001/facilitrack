<?php
/**
 * Superadmin Dashboard (Platform Overview)
 * Shows all registered organizations and platform-wide statistics.
 */
require_once __DIR__ . '/../includes/auth.php';
require_role('superadmin');

$page_title = 'Platform Overview';
$user = current_user();
$db = getDBConnection();

// Global Stats
$total_orgs = $db->query("SELECT COUNT(*) FROM organizations")->fetchColumn();
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_requests = $db->query("SELECT COUNT(*) FROM maintenance_requests")->fetchColumn();

// Fetch all organizations with their stats
$stmt = $db->query("
    SELECT o.*,
           (SELECT COUNT(*) FROM users u WHERE u.organization_id = o.id) as staff_count,
           (SELECT COUNT(*) FROM maintenance_requests mr WHERE mr.organization_id = o.id) as request_count,
           (SELECT full_name FROM users u WHERE u.organization_id = o.id AND u.role = 'admin' LIMIT 1) as admin_name
    FROM organizations o
    ORDER BY o.created_at DESC
");
$organizations = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Platform Overview</h1>
        <p>Manage and monitor all organizations registered on FaciliTrack.</p>
    </div>
</div>

<div class="stat-cards">
    <div class="stat-card stat-total">
        <div class="stat-icon"><i class="fas fa-building"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($total_orgs); ?></h3>
            <p>Total Companies</p>
        </div>
    </div>
    <div class="stat-card stat-users">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($total_users); ?></h3>
            <p>Total Users</p>
        </div>
    </div>
    <div class="stat-card stat-progress">
        <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($total_requests); ?></h3>
            <p>Total Requests Logged</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="section-header">
        <h2><i class="fas fa-list"></i> Registered Organizations</h2>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Organization</th>
                    <th>Code</th>
                    <th>Size (Stated)</th>
                    <th>Registered Staff</th>
                    <th>Total Requests</th>
                    <th>Primary Admin</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($organizations as $org): ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <?php if ($org['logo_path']): ?>
                                <img src="<?php echo APP_URL . '/' . htmlspecialchars($org['logo_path']); ?>" alt="Logo" style="width:24px;height:24px;border-radius:4px;object-fit:contain;border:1px solid #E5E7EB;background:#fff;">
                            <?php else: ?>
                                <div style="width:24px;height:24px;border-radius:4px;background:var(--primary-bg);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:10px;"><i class="fas fa-building"></i></div>
                            <?php endif; ?>
                            <strong><?php echo htmlspecialchars($org['name']); ?></strong>
                        </div>
                    </td>
                    <td><code><?php echo htmlspecialchars($org['org_code']); ?></code></td>
                    <td><?php echo htmlspecialchars($org['org_size']); ?></td>
                    <td>
                        <span class="status-badge" style="background:#EFF6FF;color:#1D4ED8;font-weight:600;">
                            <?php echo number_format($org['staff_count']); ?> Users
                        </span>
                    </td>
                    <td><?php echo number_format($org['request_count']); ?></td>
                    <td><?php echo htmlspecialchars($org['admin_name'] ?? 'N/A'); ?></td>
                    <td>
                        <?php if ($org['is_active']): ?>
                            <span class="status-badge status-resolved">Active</span>
                        <?php else: ?>
                            <span class="status-badge status-closed">Inactive</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (count($organizations) === 0): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px;">
                        No organizations registered yet.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
