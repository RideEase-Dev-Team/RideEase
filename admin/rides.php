<?php
// ============================================================
// RideEase – Admin Ride Monitoring
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireAdmin();

$db = getDB();

$filter = sanitize($_GET['status'] ?? 'all');

try {
    $sql = "
        SELECT r.*, u1.name as passenger_name, u2.name as driver_name, p.status as payment_status
        FROM rides r
        JOIN users u1 ON r.passenger_id = u1.id
        LEFT JOIN drivers d ON r.driver_id = d.id
        LEFT JOIN users u2 ON d.user_id = u2.id
        LEFT JOIN payments p ON p.ride_id = r.id
    ";
    
    if ($filter !== 'all') {
        $sql .= " WHERE r.status = ?";
        $stmt = $db->prepare($sql . " ORDER BY r.id DESC");
        $stmt->execute([$filter]);
    } else {
        $stmt = $db->query($sql . " ORDER BY r.id DESC");
    }








    
    $rides = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error compiling rides monitor registry.");
}









$pageTitle = "Ride Monitoring";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Admin Dashboard</a></li>
            <li><a href="users.php"><i class="fa-solid fa-users"></i> User Accounts</a></li>
            <li><a href="drivers.php"><i class="fa-solid fa-id-card"></i> Driver Partners</a></li>
            <li><a href="vehicles.php"><i class="fa-solid fa-truck-pickup"></i> Cab Approvals</a></li>
            <li><a href="rides.php" class="active"><i class="fa-solid fa-map-location"></i> Ride Monitors</a></li>
            <li><a href="coupons.php"><i class="fa-solid fa-ticket"></i> Promo Coupons</a></li>
            <li><a href="pricing.php"><i class="fa-solid fa-circle-dollar-to-slot"></i> Peak Surcharges</a></li>
            <li><a href="sos_alerts.php"><i class="fa-solid fa-triangle-exclamation"></i> SOS Dispatches</a></li>
            <li><a href="complaints.php"><i class="fa-solid fa-headset"></i> Complaint Tickets</a></li>
            <li><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Sales Reports</a></li>
        </ul>
    </aside>

    <div class="dashboard-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
            <div>
                <h1 class="gradient-text">Active Rides Monitor</h1>
                <p class="text-secondary">Track ongoing transits and ride history across Dhaka</p>
            </div>
            
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="rides.php?status=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding:0.4rem 0.8rem; font-size:0.8rem;">All</a>
                <a href="rides.php?status=pending" class="btn <?php echo $filter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding:0.4rem 0.8rem; font-size:0.8rem;">Pending</a>
                <a href="rides.php?status=assigned" class="btn <?php echo $filter === 'assigned' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding:0.4rem 0.8rem; font-size:0.8rem;">Assigned</a>
                <a href="rides.php?status=on_ride" class="btn <?php echo $filter === 'on_ride' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding:0.4rem 0.8rem; font-size:0.8rem;">On Ride</a>
                <a href="rides.php?status=completed" class="btn <?php echo $filter === 'completed' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding:0.4rem 0.8rem; font-size:0.8rem;">Completed</a>
                <a href="rides.php?status=cancelled" class="btn <?php echo $filter === 'cancelled' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding:0.4rem 0.8rem; font-size:0.8rem;">Cancelled</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Ride ID</th>
                            <th>Passenger</th>
                            <th>Driver</th>
                            <th>Pickup Location</th>
                            <th>Destination</th>
                            <th>Distance</th>
                            <th>Final Fare</th>
                            <th>Payment</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rides)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No rides matching status criteria found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rides as $r): ?>
                                <tr>
                                    <td>#<?php echo $r['id']; ?></td>
                                    <td><strong><?php echo sanitize($r['passenger_name']); ?></strong></td>
                                    <td><?php echo $r['driver_name'] ? sanitize($r['driver_name']) : '<span class="text-muted">Unassigned</span>'; ?></td>
                                    <td><div style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo sanitize($r['pickup_location']); ?>"><?php echo sanitize($r['pickup_location']); ?></div></td>
                                    <td><div style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo sanitize($r['destination']); ?>"><?php echo sanitize($r['destination']); ?></div></td>
                                    <td><?php echo $r['distance_km']; ?> km</td>
                                    <td><?php echo formatBDT($r['final_fare']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $r['payment_status'] === 'completed' ? 'badge-completed' : 'badge-pending'; ?>">
                                            <?php echo $r['payment_status'] === 'completed' ? 'Paid' : 'Unpaid'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span>
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








































