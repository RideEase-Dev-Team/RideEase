<?php
// ============================================================
// RideEase – Passenger Dashboard
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requirePassenger();

$db = getDB();
$userId = currentUserId();

// 1. Check for any active ongoing ride (status != completed and != cancelled)
$activeRide = null;
try {
    $activeStmt = $db->prepare("
        SELECT r.*, u.name as driver_name, u.phone as driver_phone, v.make, v.model, v.plate_no 
        FROM rides r
        LEFT JOIN drivers d ON r.driver_id = d.id
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN vehicles v ON v.driver_id = d.id
        WHERE r.passenger_id = ? AND r.status IN ('pending', 'assigned', 'on_ride')
        ORDER BY r.id DESC LIMIT 1
    ");
    $activeStmt->execute([$userId]);
    $activeRide = $activeStmt->fetch();
} catch (PDOException $e) {
    error_log("Active ride check error: " . $e->getMessage());
}

// 2. Fetch completed/cancelled ride history
$rides = [];
try {
    $historyStmt = $db->prepare("
        SELECT r.*, u.name as driver_name, ra.rating
        FROM rides r
        LEFT JOIN drivers d ON r.driver_id = d.id
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN ratings ra ON ra.ride_id = r.id
        WHERE r.passenger_id = ? AND r.status IN ('completed', 'cancelled')
        ORDER BY r.id DESC LIMIT 5
    ");
    $historyStmt->execute([$userId]);
    $rides = $historyStmt->fetchAll();
} catch (PDOException $e) {
    error_log("History check error: " . $e->getMessage());
}

// 3. Count total rides
$totalRides = 0;
try {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM rides WHERE passenger_id = ?");
    $countStmt->execute([$userId]);
    $totalRides = $countStmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Count check error: " . $e->getMessage());
}

$pageTitle = "Passenger Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="book_ride.php"><i class="fa-solid fa-map-location-dot"></i> Book Ride</a></li>
            <li><a href="ride_history.php"><i class="fa-solid fa-list-ul"></i> Ride History</a></li>
            <li><a href="profile.php"><i class="fa-solid fa-user-gear"></i> Account Settings</a></li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <div class="dashboard-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
            <div>
                <h1 class="gradient-text" style="margin-bottom:0.2rem;">Hello, <?php echo sanitize(currentUserName()); ?>!</h1>
                <p class="text-secondary">Where would you like to travel today?</p>
            </div>
            
            <a href="book_ride.php" class="btn btn-primary"><i class="fa-solid fa-compass"></i> Book A Ride</a>
        </div>

        <!-- Float active ride details if exists -->
        <?php if ($activeRide): ?>
            <div class="card" style="border: 2px solid var(--accent-cyan); background-color: rgba(0, 240, 255, 0.05); margin-bottom: 2rem;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <span class="badge badge-<?php echo $activeRide['status']; ?>" style="margin-bottom: 0.8rem;">
                            Active Trip: <?php echo ucfirst($activeRide['status']); ?>
                        </span>
                        <h3>Booking Details (ID: #<?php echo $activeRide['id']; ?>)</h3>
                        <p><strong>From:</strong> <?php echo sanitize($activeRide['pickup_location']); ?></p>
                        <p><strong>To:</strong> <?php echo sanitize($activeRide['destination']); ?></p>
                        
                        <?php if ($activeRide['status'] === 'assigned' || $activeRide['status'] === 'on_ride'): ?>
                            <p style="margin-top: 0.5rem;"><strong>Driver Assigned:</strong> <?php echo sanitize($activeRide['driver_name']); ?> (<?php echo sanitize($activeRide['driver_phone']); ?>)</p>
                            <p><strong>Vehicle:</strong> <?php echo sanitize($activeRide['make'] . ' ' . $activeRide['model'] . ' [' . $activeRide['plate_no'] . ']'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div style="text-align:right;">
                        <a href="track_ride.php?ride_id=<?php echo $activeRide['id']; ?>" class="btn btn-primary" style="margin-bottom:0.5rem;"><i class="fa-solid fa-map-location-dot"></i> Live Map Tracker</a>
                        <?php if ($activeRide['status'] === 'pending' || $activeRide['status'] === 'assigned'): ?>
                            <br>
                            <a href="track_ride.php?ride_id=<?php echo $activeRide['id']; ?>&cancel=1" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.85rem;"><i class="fa-solid fa-rectangle-xmark"></i> Cancel Booking</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Summary Stats Widget -->
        <div class="grid-3" style="margin-bottom: 2rem;">
            <div class="card stat-card">
                <span class="stat-value"><?php echo $totalRides; ?></span>
                <span class="stat-label">Total Rides Completed</span>
            </div>
            <div class="card stat-card">
                <span class="stat-value">৳ 12.00</span>
                <span class="stat-label">Price Per Kilometer</span>
            </div>
            <div class="card stat-card">
                <span class="stat-value">৳ 50.00</span>
                <span class="stat-label">Platform Base Fare</span>
            </div>
        </div>

        <!-- Recent Rides History Table -->
        <div class="card">
            <h3>Recent Rides</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pickup Location</th>
                            <th>Destination</th>
                            <th>Fare</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rides)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No rides requested yet. Click "Book Ride" to start.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rides as $r): ?>
                                <tr>
                                    <td>#<?php echo $r['id']; ?></td>
                                    <td><?php echo sanitize($r['pickup_location']); ?></td>
                                    <td><?php echo sanitize($r['destination']); ?></td>
                                    <td><?php echo formatBDT($r['final_fare']); ?></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($r['created_at'])); ?></td>
                                    <td><span class="badge badge-<?php echo $r['status']; ?>"><?php echo $r['status']; ?></span></td>
                                    <td>
                                        <?php if ($r['status'] === 'completed' && !$r['rating']): ?>
                                            <a href="track_ride.php?ride_id=<?php echo $r['id']; ?>" class="btn btn-secondary" style="padding: 0.3rem 0.8rem; font-size:0.8rem;">Rate Driver</a>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size:0.85rem;">Reviewed</span>
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
