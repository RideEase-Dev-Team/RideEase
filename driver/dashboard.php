<?php
// ============================================================
// RideEase – Driver Operations Dashboard Hub
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireDriver();

$db = getDB();
$userId = currentUserId();

// 1. Fetch driver profile stats
try {
    $driverStmt = $db->prepare("
        SELECT d.*, u.name, u.phone 
        FROM drivers d 
        JOIN users u ON d.user_id = u.id 
        WHERE d.user_id = ?
    ");
    $driverStmt->execute([$userId]);
    $driver = $driverStmt->fetch();

    if (!$driver) {
        // Driver profile deleted somehow
        session_unset();
        session_destroy();
        redirect('/auth/login.php?msg=Profile+not+found');
    }







} catch (PDOException $e) {
    die("Error loading driver profile details.");
}








// 2. Fetch current ongoing assigned ride (assigned or on_ride)
$activeRide = null;
try {
    $activeStmt = $db->prepare("
        SELECT r.*, u.name as passenger_name, u.phone as passenger_phone 
        FROM rides r
        JOIN users u ON r.passenger_id = u.id
        WHERE r.driver_id = ? AND r.status IN ('assigned', 'on_ride')
        ORDER BY r.id DESC LIMIT 1
    ");
    $activeStmt->execute([$driver['id']]);
    $activeRide = $activeStmt->fetch();
} catch (PDOException $e) {
    error_log("Driver active ride fetch error: " . $e->getMessage());
}








// 3. Compute driver total net earnings
$totalEarnings = 0.00;
try {
    $earnStmt = $db->prepare("SELECT SUM(net_amount) FROM driver_earnings WHERE driver_id = ?");
    $earnStmt->execute([$driver['id']]);
    $totalEarnings = floatval($earnStmt->fetchColumn() ?? 0.00);
} catch (PDOException $e) {
    error_log("Driver earnings fetch error: " . $e->getMessage());
}








$pageTitle = "Driver Hub";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Driver Hub</a></li>
            <li><a href="earnings.php"><i class="fa-solid fa-wallet"></i> My Earnings</a></li>
            <li><a href="ride_history.php"><i class="fa-solid fa-list-ul"></i> Ride History</a></li>
            <li><a href="profile.php"><i class="fa-solid fa-user-gear"></i> Profile Settings</a></li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <div class="dashboard-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
            <div>
                <h1 class="gradient-text">Driver Hub Control</h1>
                <p class="text-secondary">Welcome, <?php echo sanitize($driver['name']); ?>! Manage availability and accepts.</p>
            </div>
            
            <!-- Availability Toggle Widget -->
            <div class="card" style="padding: 0.8rem 1.2rem; display:flex; align-items:center; gap:15px; border-color: var(--accent-cyan);">
                <span id="availability-status-label" class="badge <?php echo $driver['is_available'] ? 'badge-completed' : 'badge-cancelled'; ?>">
                    <?php echo $driver['is_available'] ? 'Online & Available' : 'Offline'; ?>
                </span>
                
                <label class="switch" style="position:relative; display:inline-block; width:50px; height:26px;">
                    <input type="checkbox" id="availability-checkbox" onchange="toggleAvailability(this)" <?php echo $driver['is_available'] ? 'checked' : ''; ?> style="opacity:0; width:0; height:0;">
                    <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color: var(--bg-tertiary); border:1px solid var(--border-color); transition:.4s; border-radius:34px;"></span>
                </label>
            </div>
        </div>

        <style>
            /* Custom CSS Slider styles */
            #availability-checkbox:checked + .slider {
                background-color: var(--accent-cyan) !important;
            }







            .slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 4px;
                bottom: 3px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }







            #availability-checkbox:checked + .slider:before {
                transform: translateX(24px);
            }







        </style>

        <!-- Driver Stats Summary Cards -->
        <div class="grid-3" style="margin-bottom: 2rem;">
            <div class="card stat-card">
                <span class="stat-value"><?php echo formatBDT($totalEarnings); ?></span>
                <span class="stat-label">Total Net Earnings</span>
            </div>
            <div class="card stat-card">
                <span class="stat-value"><?php echo $driver['total_trips']; ?></span>
                <span class="stat-label">Completed Rides</span>
            </div>
            <div class="card stat-card">
                <span class="stat-value">
                    <?php echo number_format($driver['avg_rating'], 2); ?> <i class="fa-solid fa-star" style="color:var(--warning); font-size:1.2rem;"></i>
                </span>
                <span class="stat-label">Average Star Rating</span>
            </div>
        </div>

        <!-- Section: Active Ride operations OR Search Poller -->
        <?php if ($activeRide): ?>
            <!-- Currently active trip controller -->
            <div class="card" style="border: 2px solid var(--accent-purple); background: rgba(138, 43, 226, 0.05); margin-bottom: 2rem; animation: slideDown 0.4s ease;">
                <span class="badge badge-<?php echo $activeRide['status']; ?>" style="margin-bottom: 1rem;">
                    Trip Status: <?php echo ucfirst($activeRide['status']); ?>
                </span>
                
                <h3>Ongoing Trip #<?php echo $activeRide['id']; ?></h3>
                
                <div class="grid-2" style="margin: 1.5rem 0;">
                    <div>
                        <p><strong>Passenger Rider:</strong> <?php echo sanitize($activeRide['passenger_name']); ?></p>
                        <p><strong>Contact phone:</strong> <?php echo sanitize($activeRide['passenger_phone']); ?></p>
                    </div>
                    <div>
                        <p><strong>From:</strong> <?php echo sanitize($activeRide['pickup_location']); ?></p>
                        <p><strong>To:</strong> <?php echo sanitize($activeRide['destination']); ?></p>
                    </div>
                </div>

                <div style="border-top:1px solid var(--border-color); padding-top:1.5rem; display:flex; gap:15px; justify-content:space-between; align-items:center;">
                    <div>
                        <span class="text-secondary">Expected Total Fare: </span>
                        <strong class="gradient-text" style="font-size:1.2rem;"><?php echo formatBDT($activeRide['final_fare']); ?></strong>
                    </div>

                    <div>
                        <?php if ($activeRide['status'] === 'assigned'): ?>
                            <!-- Start Trip action -->
                            <form action="../api/update_status.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                <input type="hidden" name="ride_id" value="<?php echo $activeRide['id']; ?>">
                                <input type="hidden" name="status" value="on_ride">
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-taxi"></i> Start Ride Trip</button>
                            </form>
                        <?php elseif ($activeRide['status'] === 'on_ride'): ?>
                            <!-- Complete Trip action -->
                            <form action="../api/update_status.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                <input type="hidden" name="ride_id" value="<?php echo $activeRide['id']; ?>">
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="btn btn-success"><i class="fa-solid fa-flag-checkered"></i> Complete & End Ride</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Listener poller for available drivers -->
            <div class="card" id="incoming-ride-container">
                <!-- Javascript will poll and auto fill requests here -->
                <p class="text-muted">Loading network search status...</p>
            </div>
        <?php endif; ?>

        <!-- Emergency / SOS Alert Warning Banner -->
        <div class="card" style="border-color: var(--danger); background: rgba(255, 23, 68, 0.03); margin-top:2rem;">
            <div style="display:flex; align-items:center; gap:15px;">
                <i class="fa-solid fa-circle-exclamation" style="font-size:2rem; color:var(--danger);"></i>
                <div>
                    <h4 style="margin:0; color:var(--danger);">SOS Emergency Assistance</h4>
                    <p class="text-secondary" style="font-size:0.85rem;">In case of any safety emergency during active transits, utilize user dashboards or call public dispatch systems immediately.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/assets/js/driver.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Start polling if driver is checked online and has no active trip
        const isAvailable = document.getElementById('availability-checkbox').checked;
        const hasActiveTrip = <?php echo $activeRide ? 'true' : 'false'; ?>;
        
        if (isAvailable && !hasActiveTrip) {
            startPollingForRides();
        }







    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
 


































