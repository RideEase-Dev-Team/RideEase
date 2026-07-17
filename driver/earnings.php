<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireDriver();

$db = getDB();
$userId = currentUserId();

try {
    $driverStmt = $db->prepare("SELECT id FROM drivers WHERE user_id = ?");
    $driverStmt->execute([$userId]);
    $driverId = $driverStmt->fetchColumn();

    if (!$driverId) {
        setFlash('danger', "Driver profile not found.");
        redirect('/auth/login.php');
    }
} catch (PDOException $e) {
    die("Database config error.");
}

$gross = 0.00;
$commission = 0.00;
$net = 0.00;
try {
    $statsStmt = $db->prepare("
        SELECT SUM(gross_amount) as gross_s, SUM(commission_amount) as comm_s, SUM(net_amount) as net_s 
        FROM driver_earnings 
        WHERE driver_id = ?
    ");
    $statsStmt->execute([$driverId]);
    $stats = $statsStmt->fetch();
    
    $gross = floatval($stats['gross_s'] ?? 0.00);
    $commission = floatval($stats['comm_s'] ?? 0.00);
    $net = floatval($stats['net_s'] ?? 0.00);
} catch (PDOException $e) {
    error_log("Stats fetch error: " . $e->getMessage());
}

$history = [];
try {
    $histStmt = $db->prepare("
        SELECT e.*, r.pickup_location, r.destination, r.distance_km, r.payment_method
        FROM driver_earnings e
        JOIN rides r ON e.ride_id = r.id
        WHERE e.driver_id = ?
        ORDER BY e.id DESC
    ");
    $histStmt->execute([$driverId]);
    $history = $histStmt->fetchAll();
} catch (PDOException $e) {
    error_log("History fetch error: " . $e->getMessage());
}

$pageTitle = "Earnings Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Driver Hub</a></li>
            <li><a href="earnings.php" class="active"><i class="fa-solid fa-wallet"></i> My Earnings</a></li>
            <li><a href="ride_history.php"><i class="fa-solid fa-list-ul"></i> Ride History</a></li>
            <li><a href="profile.php"><i class="fa-solid fa-user-gear"></i> Profile Settings</a></li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <div class="dashboard-content">
        <h1 class="gradient-text">Earnings & Payout Analytics</h1>
        <p class="text-secondary" style="margin-bottom: 2rem;">Real-time breakdown of gross fares, deductions, and payouts</p>

        <!-- Earnings Summary Widget Cards -->
        <div class="grid-3" style="margin-bottom: 2rem;">
            <div class="card stat-card" style="border-color: var(--accent-cyan);">
                <span class="stat-value" style="color:var(--accent-cyan);"><?php echo formatBDT($gross); ?></span>
                <span class="stat-label">Total Gross Earnings</span>
            </div>
            <div class="card stat-card" style="border-color: var(--danger);">
                <span class="stat-value" style="color:var(--danger);"><?php echo formatBDT($commission); ?></span>
                <span class="stat-label">Platform Fees (20%)</span>
            </div>
            <div class="card stat-card" style="border-color: var(--success);">
                <span class="stat-value" style="color:var(--success);"><?php echo formatBDT($net); ?></span>
                <span class="stat-label">Net Take-home Payout</span>
            </div>
        </div>

        <!-- Earnings history table -->
        <div class="card">
            <h3>Earnings Log Breakdown</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Transaction Ref</th>
                            <th>Route Taken</th>
                            <th>Distance</th>
                            <th>Gross Fare</th>
                            <th>Deducted Comm.</th>
                            <th>My Net Payout</th>
                            <th>Channel</th>
                            <th>Date Settle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No completed earnings reported yet. Settle payments on passenger dashboard to test.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $item): ?>
                                <tr>
                                    <td style="color:var(--accent-cyan); font-weight:600;">EARN-<?php echo $item['id']; ?></td>
                                    <td>
                                        <div style="font-size:0.85rem; max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                            <strong>From:</strong> <?php echo sanitize($item['pickup_location']); ?><br>
                                            <strong>To:</strong> <?php echo sanitize($item['destination']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo $item['distance_km']; ?> km</td>
                                    <td><?php echo formatBDT($item['gross_amount']); ?></td>
                                    <td style="color:var(--danger);">-<?php echo formatBDT($item['commission_amount']); ?></td>
                                    <td style="color:var(--success); font-weight:600;"><?php echo formatBDT($item['net_amount']); ?></td>
                                    <td style="text-transform:uppercase; font-size:0.85rem;">
                                        <i class="fa-solid <?php echo $item['payment_method'] === 'cash' ? 'fa-wallet' : ($item['payment_method'] === 'bkash' ? 'fa-mobile-screen-button' : 'fa-credit-card'); ?>"></i>
                                        <?php echo $item['payment_method']; ?>
                                    </td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($item['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
