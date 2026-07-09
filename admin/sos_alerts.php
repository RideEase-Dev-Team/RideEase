<?php
// ============================================================
// RideEase – Admin Emergency SOS Dashboard
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireAdmin();

$db = getDB();

// Handle Resolve SOS Alert Action
if (isset($_GET['resolve'])) {
    $sosId = intval($_GET['resolve']);
    try {
        $stmt = $db->prepare("
            UPDATE sos_alerts 
            SET is_resolved = 1, resolved_by = ?, resolved_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([currentUserId(), $sosId]);
        setFlash('success', "SOS alert #$sosId marked as resolved.");
    } catch (PDOException $e) {
        setFlash('danger', "Failed to resolve emergency alert.");
    }


    redirect('/admin/sos_alerts.php');
}



// Fetch all SOS alerts
try {
    $stmt = $db->query("
        SELECT s.*, u.name as user_name, u.phone as user_phone, r.pickup_location, r.destination 
        FROM sos_alerts s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN rides r ON s.ride_id = r.id
        ORDER BY s.is_resolved ASC, s.id DESC
    ");
    $alerts = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error compiling emergency alerts.");
}



$pageTitle = "SOS Emergency Panel";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Admin Dashboard</a></li>
            <li><a href="users.php"><i class="fa-solid fa-users"></i> User Accounts</a></li>
            <li><a href="drivers.php"><i class="fa-solid fa-id-card"></i> Driver Partners</a></li>
            <li><a href="vehicles.php"><i class="fa-solid fa-truck-pickup"></i> Cab Approvals</a></li>
            <li><a href="rides.php"><i class="fa-solid fa-map-location"></i> Ride Monitors</a></li>
            <li><a href="coupons.php"><i class="fa-solid fa-ticket"></i> Promo Coupons</a></li>
            <li><a href="pricing.php"><i class="fa-solid fa-circle-dollar-to-slot"></i> Peak Surcharges</a></li>
            <li><a href="sos_alerts.php" class="active"><i class="fa-solid fa-triangle-exclamation"></i> SOS Dispatches</a></li>
            <li><a href="complaints.php"><i class="fa-solid fa-headset"></i> Complaint Tickets</a></li>
            <li><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Sales Reports</a></li>
        </ul>
    </aside>

    <div class="dashboard-content">
        <h1 class="gradient-text">Emergency Safety & SOS Alerts</h1>
        <p class="text-secondary" style="margin-bottom: 2rem;">Monitor distress alerts submitted from passenger transits in real-time</p>

        <div class="card">
            <h3>SOS Alerts Log</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Alert ID</th>
                            <th>Rider / Passenger</th>
                            <th>Phone Contact</th>
                            <th>Ride ID</th>
                            <th>Emergency Message</th>
                            <th>Trigger Time</th>
                            <th>Status State</th>
                            <th>Controls</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($alerts)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No emergency SOS alerts triggered. Safe system environment.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($alerts as $a): ?>
                                <tr style="<?php echo !$a['is_resolved'] ? 'background-color: rgba(255, 23, 68, 0.03);' : ''; ?>">
                                    <td>#<?php echo $a['id']; ?></td>
                                    <td><strong><?php echo sanitize($a['user_name']); ?></strong></td>
                                    <td><?php echo sanitize($a['user_phone']); ?></td>
                                    <td>
                                        <?php if ($a['ride_id']): ?>
                                            <a href="rides.php?q=<?php echo $a['ride_id']; ?>" style="color:var(--accent-cyan);">#<?php echo $a['ride_id']; ?></a>
                                        <?php else: ?>
                                            <span class="text-muted">No Ride Link</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:#fff; font-weight:600;"><?php echo sanitize($a['message']); ?></td>
                                    <td><?php echo timeAgo($a['created_at']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $a['is_resolved'] ? 'badge-completed' : 'badge-cancelled'; ?>">
                                            <?php echo $a['is_resolved'] ? 'Resolved' : 'CRITICAL ACTIVE'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!$a['is_resolved']): ?>
                                            <a href="sos_alerts.php?resolve=<?php echo $a['id']; ?>" class="btn btn-success" style="padding:0.3rem 0.6rem; font-size:0.8rem;"><i class="fa-solid fa-circle-check"></i> Settle Alert</a>
                                        <?php else: ?>
                                            <span style="font-size:0.8rem;" class="text-muted">Settled</span>
                                        <?php endif; ?>
                                    </td>
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










