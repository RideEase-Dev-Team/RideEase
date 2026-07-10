<?php
// ============================================================
// RideEase – Admin Dashboard Home (Design layout for graphs & log custom analytics queries)
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireAdmin();

$db = getDB();

// Fetch summary metrics
$passenger_count = 0;
$driver_count = 0;
$active_rides = 0;
$pending_sos = 0;

try {
    $passenger_count = $db->query("SELECT COUNT(*) FROM users WHERE role = 'passenger'")->fetchColumn();
    $driver_count = $db->query("SELECT COUNT(*) FROM drivers")->fetchColumn();
    $active_rides = $db->query("SELECT COUNT(*) FROM rides WHERE status IN ('pending', 'assigned', 'on_ride')")->fetchColumn();
    $pending_sos = $db->query("SELECT COUNT(*) FROM sos_alerts WHERE is_resolved = 0")->fetchColumn();
    
    // Fetch pending SOS alerts
    $sos_alerts = $db->query("
        SELECT s.*, u.name as user_name, u.phone as user_phone, r.pickup_location, r.destination 
        FROM sos_alerts s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN rides r ON s.ride_id = r.id
        WHERE s.is_resolved = 0
        ORDER BY s.id DESC LIMIT 5
    ")->fetchAll();
    
    // Fetch recent ride tracking records
    $recent_rides = $db->query("
        SELECT r.*, u1.name as passenger_name, u2.name as driver_name
        FROM rides r
        JOIN users u1 ON r.passenger_id = u1.id
        LEFT JOIN drivers d ON r.driver_id = d.id
        LEFT JOIN users u2 ON d.user_id = u2.id
        ORDER BY r.id DESC LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    die("Database stats fetch error.");
}

// Prepare chart datasets for JavaScript
$revLabels = [];
$revVals = [];
try {
    // Last 7 days revenue query
    $revStmt = $db->query("
        SELECT DATE_FORMAT(created_at, '%b %d') as r_day, SUM(amount) as r_sum
        FROM payments
        WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC
    ");
    $revStats = $revStmt->fetchAll();
    foreach ($revStats as $rs) {
        $revLabels[] = $rs['r_day'];
        $revVals[] = floatval($rs['r_sum']);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}

$rideStats = ['completed' => 0, 'pending' => 0, 'assigned' => 0, 'on_ride' => 0, 'cancelled' => 0];
try {
    $statusStmt = $db->query("SELECT status, COUNT(*) as qty FROM rides GROUP BY status");
    $dbStats = $statusStmt->fetchAll();
    foreach ($dbStats as $ds) {
        $rideStats[$ds['status']] = intval($ds['qty']);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}

$pageTitle = "Admin Panel";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Admin Dashboard</a></li>
            <li><a href="users.php"><i class="fa-solid fa-users"></i> User Accounts</a></li>
            <li><a href="drivers.php"><i class="fa-solid fa-id-card"></i> Driver Partners</a></li>
            <li><a href="vehicles.php"><i class="fa-solid fa-truck-pickup"></i> Cab Approvals</a></li>
            <li><a href="rides.php"><i class="fa-solid fa-map-location"></i> Ride Monitors</a></li>
            <li><a href="coupons.php"><i class="fa-solid fa-ticket"></i> Promo Coupons</a></li>
            <li><a href="pricing.php"><i class="fa-solid fa-circle-dollar-to-slot"></i> Peak Surcharges</a></li>
            <li><a href="sos_alerts.php"><i class="fa-solid fa-triangle-exclamation"></i> SOS Dispatches (<?php echo $pending_sos; ?>)</a></li>
            <li><a href="complaints.php"><i class="fa-solid fa-headset"></i> Complaint Tickets</a></li>
            <li><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Sales Reports</a></li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <div class="dashboard-content">
        <h1 class="gradient-text" style="margin-bottom:0.2rem;">Admin Dashboard</h1>
        <p class="text-secondary" style="margin-bottom: 2rem;">Real-time dispatch controls and analytics tracking</p>

        <!-- KPI Cards -->
        <div class="grid-4" style="margin-bottom: 2rem;">
            <div class="card stat-card">
                <span class="stat-value"><?php echo $passenger_count; ?></span>
                <span class="stat-label">Total Passengers</span>
            </div>
            <div class="card stat-card">
                <span class="stat-value"><?php echo $driver_count; ?></span>
                <span class="stat-label">Total Drivers</span>
            </div>
            <div class="card stat-card" style="border-color: var(--accent-cyan);">
                <span class="stat-value" style="color:var(--accent-cyan);"><?php echo $active_rides; ?></span>
                <span class="stat-label">Active Trips Online</span>
            </div>
            <div class="card stat-card" style="border-color: <?php echo $pending_sos > 0 ? 'var(--danger)' : 'var(--border-color)'; ?>;">
                <span class="stat-value" style="color:<?php echo $pending_sos > 0 ? 'var(--danger)' : 'var(--success)'; ?>;"><?php echo $pending_sos; ?></span>
                <span class="stat-label">Active SOS Alerts</span>
            </div>
        </div>

        <!-- SOS Alerts Section -->
        <?php if (!empty($sos_alerts)): ?>
            <div class="card" style="border: 2px solid var(--danger); background: rgba(255, 23, 68, 0.05); margin-bottom: 2rem; animation: slideDown 0.4s ease;">
                <h3 style="color:var(--danger);"><i class="fa-solid fa-bell fa-bounce" style="margin-right:10px;"></i> PENDING EMERGENCY SOS ALERTS!</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Alert ID</th>
                                <th>Rider</th>
                                <th>Phone</th>
                                <th>Ride ID</th>
                                <th>Message</th>
                                <th>Time Trigger</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sos_alerts as $alert): ?>
                                <tr>
                                    <td>#<?php echo $alert['id']; ?></td>
                                    <td><?php echo sanitize($alert['user_name']); ?></td>
                                    <td><?php echo sanitize($alert['user_phone']); ?></td>
                                    <td>#<?php echo $alert['ride_id']; ?></td>
                                    <td style="color:#fff;"><?php echo sanitize($alert['message']); ?></td>
                                    <td><?php echo timeAgo($alert['created_at']); ?></td>
                                    <td>
                                        <a href="sos_alerts.php?resolve=<?php echo $alert['id']; ?>" class="btn btn-success" style="padding: 0.3rem 0.8rem; font-size:0.8rem;"><i class="fa-solid fa-check"></i> Resolve</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Analytical Charts Grid -->
        <div class="grid-2" style="margin-bottom: 2rem;">
            <div class="card">
                <h3>Revenue Trend (Last 7 Days)</h3>
                <canvas id="revenueChart" style="max-height: 250px;"></canvas>
            </div>
            
            <div class="card">
                <h3>Ride Distribution Share</h3>
                <canvas id="rideStatsChart" style="max-height: 250px;"></canvas>
            </div>
        </div>

        <!-- Recent Rides Monitoring -->
        <div class="card">
            <h3>Recent Rides Monitor</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Ride ID</th>
                            <th>Rider</th>
                            <th>Driver</th>
                            <th>Pickup Location</th>
                            <th>Destination</th>
                            <th>Fare</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_rides as $r): ?>
                            <tr>
                                <td>#<?php echo $r['id']; ?></td>
                                <td><?php echo sanitize($r['passenger_name']); ?></td>
                                <td><?php echo $r['driver_name'] ? sanitize($r['driver_name']) : '<span class="text-muted">Searching...</span>'; ?></td>
                                <td><?php echo sanitize($r['pickup_location']); ?></td>
                                <td><?php echo sanitize($r['destination']); ?></td>
                                <td><?php echo formatBDT($r['final_fare']); ?></td>
                                <td><span class="badge badge-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Load Chart.js Rendering assets -->
<script src="<?php echo BASE_URL; ?>/assets/js/admin_charts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Init graphs with dynamically compiled arrays
        const revenueData = {
            labels: <?php echo json_encode($revLabels); ?>,
            values: <?php echo json_encode($revVals); ?>
        };
        const rideStats = <?php echo json_encode($rideStats); ?>;
        
        initAdminCharts(revenueData, rideStats);
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
