<?php
// ============================================================
// RideEase – Ride Tracking & Rating Interface
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requirePassenger();

$db = getDB();
$userId = currentUserId();

$rideId = isset($_GET['ride_id']) ? intval($_GET['ride_id']) : 0;

if (!$rideId) {
    setFlash('danger', "Invalid ride request ID.");
    redirect('/passenger/dashboard.php');
}










// Fetch ride status
try {
    $stmt = $db->prepare("
        SELECT r.*, u.name as driver_name, u.phone as driver_phone, v.make, v.model, v.plate_no, ra.rating as exist_rating
        FROM rides r
        LEFT JOIN drivers d ON r.driver_id = d.id
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN vehicles v ON v.driver_id = d.id
        LEFT JOIN ratings ra ON ra.ride_id = r.id
        WHERE r.id = ? AND r.passenger_id = ?
    ");
    $stmt->execute([$rideId, $userId]);
    $ride = $stmt->fetch();

    if (!$ride) {
        setFlash('danger', "Ride not found or access denied.");
        redirect('/passenger/dashboard.php');
    }









} catch (PDOException $e) {
    setFlash('danger', "Database error loading ride tracker.");
    redirect('/passenger/dashboard.php');
}










// Handle Cancel Request Action
if (isset($_POST['action_cancel'])) {
    verifyCsrf();
    $reason = sanitize($_POST['cancel_reason'] ?? 'Passenger cancelled');
    
    try {
        $db->beginTransaction();
        
        // Update ride status
        $updateStmt = $db->prepare("UPDATE rides SET status = 'cancelled' WHERE id = ?");
        $updateStmt->execute([$rideId]);
        
        // Add log entry
        $logStmt = $db->prepare("INSERT INTO cancellations (ride_id, cancelled_by, cancelled_by_role, reason) VALUES (?, ?, 'passenger', ?)");
        $logStmt->execute([$rideId, $userId, $reason]);
        
        $db->commit();
        setFlash('success', "Ride booking cancelled successfully.");
        redirect('/passenger/dashboard.php');
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        setFlash('danger', "Cancellation failed: " . $e->getMessage());
    }









}










// Handle Rating / Feedback submission
if (isset($_POST['submit_review'])) {
    verifyCsrf();
    $rating = intval($_POST['rating']);
    $feedback = sanitize($_POST['feedback'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        setFlash('danger', "Please select a rating between 1 and 5 stars.");
    } else {
        try {
            $db->beginTransaction();
            
            // Insert rating
            $ratingStmt = $db->prepare("INSERT INTO ratings (ride_id, driver_id, passenger_id, rating, feedback) VALUES (?, ?, ?, ?, ?)");
            $ratingStmt->execute([$rideId, $ride['driver_id'], $userId, $rating, $feedback]);
            
            // Recompute Driver Avg Rating
            $avgStmt = $db->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as count_r FROM ratings WHERE driver_id = ?");
            $avgStmt->execute([$ride['driver_id']]);
            $stats = $avgStmt->fetch();
            
            $updateDriver = $db->prepare("UPDATE drivers SET avg_rating = ?, total_trips = ? WHERE id = ?");
            $updateDriver->execute([$stats['avg_r'], $stats['count_r'], $ride['driver_id']]);
            
            $db->commit();
            setFlash('success', "Thank you for rating your ride!");
            redirect('/passenger/dashboard.php');
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            setFlash('danger', "Feedback submission failed.");
        }









    }









}










$pageTitle = "Track Ride #" . $rideId;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="book_ride.php"><i class="fa-solid fa-map-location-dot"></i> Book Ride</a></li>
            <li><a href="ride_history.php"><i class="fa-solid fa-list-ul"></i> Ride History</a></li>
            <li><a href="profile.php"><i class="fa-solid fa-user-gear"></i> Account Settings</a></li>
        </ul>
    </aside>

    <div class="dashboard-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
            <div>
                <h1 class="gradient-text">Ride Status Tracker</h1>
                <p class="text-secondary">Tracking details for booking #<?php echo $rideId; ?></p>
            </div>
            
            <?php if (in_array($ride['status'], ['assigned', 'on_ride'])): ?>
                <button class="btn btn-danger" onclick="triggerSOS(<?php echo $rideId; ?>)">
                    <i class="fa-solid fa-triangle-exclamation"></i> Trigger SOS
                </button>
            <?php endif; ?>
        </div>

        <div class="grid-2">
            <!-- Left Panel: Map & Status -->
            <div>
                <div class="card" style="margin-bottom:1.5rem;">
                    <h3>Live Status: <span class="badge badge-<?php echo $ride['status']; ?>"><?php echo ucfirst($ride['status']); ?></span></h3>
                    
                    <div style="margin: 1.5rem 0;">
                        <!-- Custom CSS progress stepper -->
                        <div style="display:flex; justify-content:space-between; position:relative;">
                            <div style="text-align:center; flex:1;">
                                <i class="fa-solid fa-circle-dot" style="color:var(--accent-cyan);"></i>
                                <div style="font-size:0.75rem; margin-top:5px; color:#fff;">Requested</div>
                            </div>
                            <div style="text-align:center; flex:1;">
                                <i class="fa-solid fa-user-check" style="color: <?php echo in_array($ride['status'], ['assigned', 'on_ride', 'completed']) ? 'var(--accent-cyan)' : 'var(--text-muted)'; ?>"></i>
                                <div style="font-size:0.75rem; margin-top:5px; color:#fff;">Assigned</div>
                            </div>
                            <div style="text-align:center; flex:1;">
                                <i class="fa-solid fa-taxi" style="color: <?php echo in_array($ride['status'], ['on_ride', 'completed']) ? 'var(--accent-cyan)' : 'var(--text-muted)'; ?>"></i>
                                <div style="font-size:0.75rem; margin-top:5px; color:#fff;">On Ride</div>
                            </div>
                            <div style="text-align:center; flex:1;">
                                <i class="fa-solid fa-flag-checkered" style="color: <?php echo $ride['status'] === 'completed' ? 'var(--success)' : 'var(--text-muted)'; ?>"></i>
                                <div style="font-size:0.75rem; margin-top:5px; color:#fff;">Completed</div>
                            </div>
                        </div>
                    </div>

                    <div id="map-tracker" class="map-container">
                        <!-- Simulated map view container -->
                        <div class="text-center" style="padding:2rem;">
                            <i class="fa-solid fa-location-crosshairs fa-spin" style="font-size:2rem; color:var(--accent-cyan); margin-bottom:1rem;"></i>
                            <p>Map tracking active. Simulated GPS routes are routing to destination.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Driver & Vehicle / Cancellation / Rating Form -->
            <div>
                <?php if ($ride['status'] === 'pending'): ?>
                    <div class="card text-center" style="padding: 3rem 1.5rem;">
                        <i class="fa-solid fa-circle-notch fa-spin" style="font-size:3.5rem; color:var(--accent-cyan); margin-bottom:1.5rem;"></i>
                        <h3 class="gradient-text">Matching with nearest Driver...</h3>
                        <p class="text-secondary" style="margin-bottom: 2rem;">Searching approved available drivers in your locality. This will refresh automatically.</p>
                        
                        <form action="track_ride.php?ride_id=<?php echo $rideId; ?>" method="POST" style="border-top:1px solid var(--border-color); padding-top:1.5rem;">
                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                            <div class="form-group">
                                <label for="cancel_reason">Reason for cancellation</label>
                                <input type="text" name="cancel_reason" id="cancel_reason" class="form-control" placeholder="E.g., Changed my mind" required>
                            </div>
                            <button type="submit" name="action_cancel" class="btn btn-danger" style="width:100%;">Cancel Request</button>
                        </form>
                    </div>
                    
                    <script>
                        // Auto-refresh search page every 4 seconds
                        setTimeout(() => { location.reload(); }, 4000);
                    </script>

                <?php elseif ($ride['status'] === 'assigned' || $ride['status'] === 'on_ride'): ?>
                    <div class="card" style="margin-bottom: 1.5rem;">
                        <h3>Driver & Vehicle Details</h3>
                        <div style="display:flex; align-items:center; gap:15px; margin: 1rem 0;">
                            <div style="background-color: var(--bg-tertiary); width: 60px; height: 60px; border-radius: 50%; display:flex; align-items:center; justify-content:center; border: 1px solid var(--border-color);">
                                <i class="fa-solid fa-user-astronaut" style="font-size:1.8rem; color:var(--accent-cyan);"></i>
                            </div>
                            <div>
                                <h4 style="margin:0;"><?php echo sanitize($ride['driver_name']); ?></h4>
                                <p class="text-secondary" style="font-size:0.85rem;"><i class="fa-solid fa-phone"></i> <?php echo sanitize($ride['driver_phone']); ?></p>
                            </div>
                        </div>
                        <div style="background:var(--bg-tertiary); padding:1rem; border-radius:8px; border:1px solid var(--border-color);">
                            <p><strong>Vehicle Model:</strong> <?php echo sanitize($ride['make'] . ' ' . $ride['model']); ?></p>
                            <p><strong>Plate number:</strong> <?php echo sanitize($ride['plate_no']); ?></p>
                        </div>
                    </div>

                    <?php if ($ride['status'] === 'assigned'): ?>
                        <div class="card">
                            <h3>Need to cancel?</h3>
                            <form action="track_ride.php?ride_id=<?php echo $rideId; ?>" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                <div class="form-group">
                                    <label for="cancel_reason">Cancellation Reason</label>
                                    <input type="text" name="cancel_reason" id="cancel_reason" class="form-control" required placeholder="E.g., Wait time too long">
                                </div>
                                <button type="submit" name="action_cancel" class="btn btn-danger" style="width:100%;">Cancel Ride</button>
                            </form>
                        </div>
                        
                        <script>
                            // Poll status check to detect when driver starts the ride
                            setTimeout(() => { location.reload(); }, 5000);
                        </script>
                    <?php else: ?>
                        <script>
                            // Poll status check to detect when driver completes the ride
                            setTimeout(() => { location.reload(); }, 5000);
                        </script>
                    <?php endif; ?>

                <?php elseif ($ride['status'] === 'completed'): ?>
                    
                    <!-- If payment not completed yet, prompt to simulate payment -->
                    <?php
                        $payCheck = $db->prepare("SELECT status FROM payments WHERE ride_id = ?");
                        $payCheck->execute([$rideId]);
                        $payStatus = $payCheck->fetchColumn();
                    ?>

                    <?php if ($payStatus !== 'completed'): ?>
                        <div class="card text-center" style="padding: 2.5rem 1.5rem; margin-bottom:1.5rem; border-color:var(--warning);">
                            <i class="fa-solid fa-wallet" style="font-size:3.5rem; color:var(--warning); margin-bottom:1.5rem;"></i>
                            <h3 class="gradient-text">Payment Pending</h3>
                            <p class="text-secondary" style="margin-bottom: 2rem;">Your ride is complete! Please settle the simulated fare of <?php echo formatBDT($ride['final_fare']); ?>.</p>
                            <a href="payment.php?ride_id=<?php echo $rideId; ?>" class="btn btn-success" style="width:100%;"><i class="fa-solid fa-credit-card"></i> Settle Payment</a>
                        </div>
                    <?php else: ?>
                        <div class="card text-center" style="padding: 2.5rem 1.5rem; margin-bottom:1.5rem; border-color:var(--success);">
                            <i class="fa-solid fa-circle-check" style="font-size:3.5rem; color:var(--success); margin-bottom:1.5rem;"></i>
                            <h3 class="gradient-text">Payment Settled</h3>
                            <p class="text-secondary">Simulated transaction successful! Fare of <?php echo formatBDT($ride['final_fare']); ?> paid.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Feedback form -->
                    <?php if (!$ride['exist_rating']): ?>
                        <div class="card">
                            <h3>Rate Driver & Vehicle</h3>
                            <form action="track_ride.php?ride_id=<?php echo $rideId; ?>" method="POST" style="margin-top:1rem;">
                                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                
                                <div class="form-group">
                                    <label>Select Rating Stars</label>
                                    <div style="display:flex; gap:15px; font-size:1.5rem; justify-content:center; margin: 1rem 0;">
                                        <label style="cursor:pointer;"><input type="radio" name="rating" value="1" required style="margin-right:5px;"><i class="fa-solid fa-star" style="color:var(--warning);"></i> 1</label>
                                        <label style="cursor:pointer;"><input type="radio" name="rating" value="2" style="margin-right:5px;"><i class="fa-solid fa-star" style="color:var(--warning);"></i> 2</label>
                                        <label style="cursor:pointer;"><input type="radio" name="rating" value="3" style="margin-right:5px;"><i class="fa-solid fa-star" style="color:var(--warning);"></i> 3</label>
                                        <label style="cursor:pointer;"><input type="radio" name="rating" value="4" style="margin-right:5px;"><i class="fa-solid fa-star" style="color:var(--warning);"></i> 4</label>
                                        <label style="cursor:pointer;"><input type="radio" name="rating" value="5" style="margin-right:5px;"><i class="fa-solid fa-star" style="color:var(--warning);"></i> 5</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="feedback">Written Feedback (Optional)</label>
                                    <textarea name="feedback" id="feedback" rows="3" class="form-control" placeholder="E.g., Friendly behavior, clean car..."></textarea>
                                </div>

                                <button type="submit" name="submit_review" class="btn btn-primary" style="width:100%;">Submit Review</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="card text-center">
                            <h3>Feedback Submitted</h3>
                            <p class="text-secondary" style="margin-top:0.5rem;">You rated this trip <strong><?php echo $ride['exist_rating']; ?>/5 <i class="fa-solid fa-star" style="color:var(--warning);"></i></strong></p>
                        </div>
                    <?php endif; ?>

                <?php elseif ($ride['status'] === 'cancelled'): ?>
                    <div class="card text-center" style="padding: 3rem 1.5rem; border-color:var(--danger);">
                        <i class="fa-solid fa-ban" style="font-size:3.5rem; color:var(--danger); margin-bottom:1.5rem;"></i>
                        <h3 class="gradient-text">Trip Cancelled</h3>
                        <p class="text-secondary">This booking was cancelled. You can request a new ride anytime from the dashboard.</p>
                        <a href="book_ride.php" class="btn btn-primary" style="width:100%; margin-top:1.5rem;">Request New Ride</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/assets/js/booking.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
       












































