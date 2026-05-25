<?php
/**
 * Reports & Analytics Page
 * Comprehensive maintenance insights for admin/manager
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['manager', 'admin']);

$page_title = 'Reports & Analytics';
$page_subtitle = 'Comprehensive maintenance insights and analytics';
$user = current_user();
$db = getDBConnection();
$org_id = get_org_id();

// Date range filter
$range = $_GET['range'] ?? '30';
$range_label = 'Last 30 Days';
if ($range === '7') { $range_label = 'Last 7 Days'; $days = 7; }
elseif ($range === '30') { $range_label = 'Last 30 Days'; $days = 30; }
elseif ($range === '90') { $range_label = 'Last 90 Days'; $days = 90; }
elseif ($range === '365') { $range_label = 'Last 12 Months'; $days = 365; }
else { $days = 30; }

$date_from = date('Y-m-d', strtotime("-{$days} days"));

// ─── OVERVIEW STATS ───
// Total requests all time
$stmt = $db->prepare("SELECT COUNT(*) as total FROM maintenance_requests WHERE organization_id = ?");
$stmt->execute([$org_id]);
$total_all = $stmt->fetch()['total'];

// Total in date range
$stmt = $db->prepare("SELECT COUNT(*) as total FROM maintenance_requests WHERE organization_id = ? AND created_at >= ?");
$stmt->execute([$org_id, $date_from]);
$total_range = $stmt->fetch()['total'];

// Resolution rate
$stmt = $db->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END) as resolved
    FROM maintenance_requests WHERE organization_id = ?");
$stmt->execute([$org_id]);
$res_data = $stmt->fetch();
$resolution_rate = $res_data['total'] > 0 ? round(($res_data['resolved'] / $res_data['total']) * 100) : 0;

// Avg response time (time from created to first status change)
$stmt = $db->prepare("SELECT AVG(TIMESTAMPDIFF(HOUR, mr.created_at, al.created_at)) as avg_hours
    FROM maintenance_requests mr 
    JOIN activity_log al ON al.request_id = mr.id 
    WHERE mr.organization_id = ? 
    AND al.new_status = 'in_progress'
    AND al.id = (SELECT MIN(id) FROM activity_log WHERE request_id = mr.id AND new_status = 'in_progress')");
$stmt->execute([$org_id]);
$avg_response = $stmt->fetch()['avg_hours'];
$avg_response_display = $avg_response ? round($avg_response) . 'h' : 'N/A';

// Active work orders (in_progress)
$stmt = $db->prepare("SELECT COUNT(*) as total FROM maintenance_requests WHERE organization_id = ? AND status = 'in_progress'");
$stmt->execute([$org_id]);
$active_orders = $stmt->fetch()['total'];

// ─── CATEGORY BREAKDOWN ───
$stmt = $db->prepare("SELECT category, COUNT(*) as count, 
    SUM(CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END) as resolved
    FROM maintenance_requests WHERE organization_id = ? AND created_at >= ?
    GROUP BY category ORDER BY count DESC");
$stmt->execute([$org_id, $date_from]);
$categories = $stmt->fetchAll();

// ─── PRIORITY BREAKDOWN ───
$stmt = $db->prepare("SELECT priority, COUNT(*) as count 
    FROM maintenance_requests WHERE organization_id = ? AND created_at >= ?
    GROUP BY priority ORDER BY FIELD(priority, 'critical', 'high', 'medium', 'low')");
$stmt->execute([$org_id, $date_from]);
$priorities = $stmt->fetchAll();

// ─── STATUS BREAKDOWN ───
$stmt = $db->prepare("SELECT status, COUNT(*) as count 
    FROM maintenance_requests WHERE organization_id = ?
    GROUP BY status ORDER BY FIELD(status, 'pending', 'in_progress', 'resolved', 'closed')");
$stmt->execute([$org_id]);
$statuses = $stmt->fetchAll();

// ─── MONTHLY TREND (last 6 months) ───
$stmt = $db->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
    FROM maintenance_requests WHERE organization_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month ORDER BY month ASC");
$stmt->execute([$org_id]);
$monthly = $stmt->fetchAll();

// ─── TECHNICIAN PERFORMANCE ───
$stmt = $db->prepare("SELECT u.full_name, u.id,
    COUNT(mr.id) as assigned,
    SUM(CASE WHEN mr.status IN ('resolved','closed') THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN mr.status = 'in_progress' THEN 1 ELSE 0 END) as active
    FROM users u 
    LEFT JOIN maintenance_requests mr ON mr.assigned_to = u.id 
    WHERE u.organization_id = ? AND u.role = 'technician' AND u.is_approved = 1
    GROUP BY u.id, u.full_name
    ORDER BY assigned DESC");
$stmt->execute([$org_id]);
$technicians = $stmt->fetchAll();

// ─── LOCATION BREAKDOWN ───
$stmt = $db->prepare("SELECT issue_location, COUNT(*) as count 
    FROM maintenance_requests WHERE organization_id = ? AND created_at >= ?
    GROUP BY issue_location ORDER BY count DESC LIMIT 10");
$stmt->execute([$org_id, $date_from]);
$locations = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Date Range Filter -->
<div class="report-toolbar">
    <div class="report-tabs">
        <a href="#overview" class="report-tab active" onclick="showTab(this,'overview')"><i class="fas fa-th-large"></i> Overview</a>
        <a href="#maintenance" class="report-tab" onclick="showTab(this,'maintenance')"><i class="fas fa-wrench"></i> Maintenance</a>
        <a href="#technicians" class="report-tab" onclick="showTab(this,'technicians')"><i class="fas fa-users"></i> Technicians</a>
        <a href="#locations" class="report-tab" onclick="showTab(this,'locations')"><i class="fas fa-map-marker-alt"></i> Locations</a>
    </div>
    <div class="report-filter">
        <select onchange="window.location='?range='+this.value" class="range-select">
            <option value="7" <?php echo $range==='7'?'selected':''; ?>>Last 7 Days</option>
            <option value="30" <?php echo $range==='30'?'selected':''; ?>>Last 30 Days</option>
            <option value="90" <?php echo $range==='90'?'selected':''; ?>>Last 90 Days</option>
            <option value="365" <?php echo $range==='365'?'selected':''; ?>>Last 12 Months</option>
        </select>
    </div>
</div>

<!-- OVERVIEW TAB -->
<div class="tab-panel active" id="tab-overview">

    <!-- Stat Cards -->
    <div class="stat-cards">
        <div class="stat-card stat-total">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-info">
                <h3><?php echo $total_all; ?></h3>
                <p>Total Requests</p>
            </div>
        </div>
        <div class="stat-card stat-progress">
            <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
            <div class="stat-info">
                <h3><?php echo $active_orders; ?></h3>
                <p>Active Work Orders</p>
            </div>
        </div>
        <div class="stat-card stat-resolved">
            <div class="stat-icon"><i class="fas fa-percentage"></i></div>
            <div class="stat-info">
                <h3><?php echo $resolution_rate; ?>%</h3>
                <p>Resolution Rate</p>
            </div>
        </div>
        <div class="stat-card stat-pending">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <h3><?php echo $avg_response_display; ?></h3>
                <p>Avg Response Time</p>
            </div>
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="report-grid">
        <div class="report-card">
            <h3 class="rc-title">Work Order Status Distribution</h3>
            <div class="status-bars">
                <?php 
                $status_colors = ['pending'=>'#F59E0B','in_progress'=>'#3B82F6','resolved'=>'#10B981','closed'=>'#6B7280'];
                $status_total = array_sum(array_column($statuses, 'count'));
                foreach ($statuses as $s): 
                    $pct = $status_total > 0 ? round(($s['count']/$status_total)*100) : 0;
                ?>
                <div class="sb-row">
                    <div class="sb-label">
                        <span class="sb-dot" style="background:<?php echo $status_colors[$s['status']] ?? '#999'; ?>"></span>
                        <?php echo ucfirst(str_replace('_',' ',$s['status'])); ?>
                    </div>
                    <div class="sb-bar-wrap">
                        <div class="sb-bar" style="width:<?php echo $pct; ?>%;background:<?php echo $status_colors[$s['status']] ?? '#999'; ?>"></div>
                    </div>
                    <div class="sb-val"><?php echo $s['count']; ?> <span>(<?php echo $pct; ?>%)</span></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="report-card">
            <h3 class="rc-title">Monthly Activity Trend</h3>
            <?php if (empty($monthly)): ?>
            <div class="empty-mini">No data available yet</div>
            <?php else: ?>
            <div class="trend-chart">
                <?php 
                $max_count = max(array_column($monthly, 'count'));
                foreach ($monthly as $m): 
                    $height = $max_count > 0 ? round(($m['count']/$max_count)*100) : 0;
                ?>
                <div class="tc-col">
                    <div class="tc-bar" style="height:<?php echo max($height, 8); ?>%">
                        <span class="tc-val"><?php echo $m['count']; ?></span>
                    </div>
                    <div class="tc-label"><?php echo date('M', strtotime($m['month'].'-01')); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MAINTENANCE TAB -->
<div class="tab-panel" id="tab-maintenance">
    <div class="report-grid">
        <div class="report-card">
            <h3 class="rc-title">Requests by Category</h3>
            <?php if (empty($categories)): ?>
            <div class="empty-mini">No data in selected range</div>
            <?php else: ?>
            <table class="report-table">
                <thead><tr><th>Category</th><th>Total</th><th>Resolved</th><th>Rate</th></tr></thead>
                <tbody>
                <?php foreach ($categories as $cat): 
                    $rate = $cat['count']>0 ? round(($cat['resolved']/$cat['count'])*100) : 0;
                ?>
                <tr>
                    <td><strong><?php echo ucfirst(str_replace('_',' ',$cat['category'])); ?></strong></td>
                    <td><?php echo $cat['count']; ?></td>
                    <td><?php echo $cat['resolved']; ?></td>
                    <td><span class="badge badge-<?php echo $rate>=70?'resolved':($rate>=40?'in_progress':'pending'); ?>"><?php echo $rate; ?>%</span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="report-card">
            <h3 class="rc-title">Priority Distribution</h3>
            <?php if (empty($priorities)): ?>
            <div class="empty-mini">No data in selected range</div>
            <?php else: ?>
            <div class="status-bars">
                <?php 
                $pri_colors = ['critical'=>'#EF4444','high'=>'#F97316','medium'=>'#F59E0B','low'=>'#10B981'];
                $pri_total = array_sum(array_column($priorities, 'count'));
                foreach ($priorities as $p): 
                    $pct = $pri_total > 0 ? round(($p['count']/$pri_total)*100) : 0;
                ?>
                <div class="sb-row">
                    <div class="sb-label">
                        <span class="sb-dot" style="background:<?php echo $pri_colors[$p['priority']] ?? '#999'; ?>"></span>
                        <?php echo ucfirst($p['priority']); ?>
                    </div>
                    <div class="sb-bar-wrap">
                        <div class="sb-bar" style="width:<?php echo $pct; ?>%;background:<?php echo $pri_colors[$p['priority']] ?? '#999'; ?>"></div>
                    </div>
                    <div class="sb-val"><?php echo $p['count']; ?> <span>(<?php echo $pct; ?>%)</span></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- TECHNICIANS TAB -->
<div class="tab-panel" id="tab-technicians">
    <div class="report-card" style="max-width:100%">
        <h3 class="rc-title">Technician Performance</h3>
        <?php if (empty($technicians)): ?>
        <div class="empty-mini">No technicians registered yet</div>
        <?php else: ?>
        <table class="report-table">
            <thead><tr><th>Technician</th><th>Assigned</th><th>Active</th><th>Completed</th><th>Completion Rate</th></tr></thead>
            <tbody>
            <?php foreach ($technicians as $t): 
                $comp_rate = $t['assigned']>0 ? round(($t['completed']/$t['assigned'])*100) : 0;
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($t['full_name']); ?></strong></td>
                <td><?php echo $t['assigned']; ?></td>
                <td><span class="badge badge-in_progress"><?php echo $t['active']; ?></span></td>
                <td><?php echo $t['completed']; ?></td>
                <td>
                    <div class="mini-progress">
                        <div class="mini-bar" style="width:<?php echo $comp_rate; ?>%;background:<?php echo $comp_rate>=70?'#10B981':($comp_rate>=40?'#F59E0B':'#EF4444'); ?>"></div>
                    </div>
                    <span style="font-size:12px;color:#64748b;margin-left:8px"><?php echo $comp_rate; ?>%</span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- LOCATIONS TAB -->
<div class="tab-panel" id="tab-locations">
    <div class="report-card" style="max-width:100%">
        <h3 class="rc-title">Top 10 Locations by Request Volume</h3>
        <?php if (empty($locations)): ?>
        <div class="empty-mini">No data in selected range</div>
        <?php else: ?>
        <div class="status-bars">
            <?php 
            $loc_max = $locations[0]['count'] ?? 1;
            foreach ($locations as $loc): 
                $pct = round(($loc['count']/$loc_max)*100);
            ?>
            <div class="sb-row">
                <div class="sb-label" style="min-width:180px"><?php echo htmlspecialchars($loc['issue_location']); ?></div>
                <div class="sb-bar-wrap">
                    <div class="sb-bar" style="width:<?php echo $pct; ?>%;background:#2563EB"></div>
                </div>
                <div class="sb-val"><?php echo $loc['count']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Report-specific styles */
.report-toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.report-tabs{display:flex;gap:4px;background:var(--gray-100);border-radius:8px;padding:3px}
.report-tab{display:flex;align-items:center;gap:6px;padding:8px 16px;border-radius:6px;font-size:13px;font-weight:600;color:var(--gray-500);cursor:pointer;transition:all .2s;text-decoration:none}
.report-tab:hover{color:var(--gray-700)}
.report-tab.active{background:var(--primary);color:var(--white)}
.range-select{padding:8px 14px;border:1.5px solid var(--gray-200);border-radius:7px;font-family:inherit;font-size:13px;color:var(--gray-700);cursor:pointer;background:var(--white);outline:none}
.range-select:focus{border-color:var(--primary)}

.report-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
.report-card{background:var(--white);border:1px solid var(--gray-200);border-radius:8px;padding:24px}
.rc-title{font-size:15px;font-weight:700;color:var(--gray-900);margin-bottom:20px;letter-spacing:-0.2px}
.empty-mini{text-align:center;padding:32px;color:var(--gray-400);font-size:13px}

/* Status bars */
.status-bars{display:flex;flex-direction:column;gap:14px}
.sb-row{display:flex;align-items:center;gap:12px}
.sb-label{display:flex;align-items:center;gap:7px;font-size:13px;font-weight:500;color:var(--gray-700);min-width:110px;flex-shrink:0}
.sb-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.sb-bar-wrap{flex:1;height:10px;background:var(--gray-100);border-radius:5px;overflow:hidden}
.sb-bar{height:100%;border-radius:5px;transition:width .5s ease}
.sb-val{font-size:13px;font-weight:600;color:var(--gray-700);min-width:60px;text-align:right}
.sb-val span{color:var(--gray-400);font-weight:400}

/* Trend chart */
.trend-chart{display:flex;align-items:flex-end;gap:12px;height:160px;padding-top:12px}
.tc-col{flex:1;display:flex;flex-direction:column;align-items:center;height:100%}
.tc-bar{width:100%;background:var(--primary);border-radius:4px 4px 0 0;display:flex;align-items:flex-start;justify-content:center;min-height:8px;transition:height .5s ease}
.tc-val{font-size:11px;font-weight:700;color:var(--white);padding-top:6px}
.tc-label{font-size:11px;color:var(--gray-400);margin-top:8px;font-weight:500}

/* Report table */
.report-table{width:100%;border-collapse:collapse}
.report-table th{text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-400);padding:8px 12px;border-bottom:2px solid var(--gray-100)}
.report-table td{padding:10px 12px;font-size:13px;color:var(--gray-700);border-bottom:1px solid var(--gray-100)}

/* Mini progress bar */
.mini-progress{display:inline-block;width:80px;height:6px;background:var(--gray-100);border-radius:3px;overflow:hidden;vertical-align:middle}
.mini-bar{height:100%;border-radius:3px}

/* Tab panels */
.tab-panel{display:none}
.tab-panel.active{display:block}

@media(max-width:768px){
    .report-grid{grid-template-columns:1fr}
    .report-tabs{flex-wrap:wrap}
    .sb-label{min-width:80px}
}
</style>

<script>
function showTab(el, name) {
    document.querySelectorAll('.report-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('tab-' + name).classList.add('active');
    return false;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
