<?php
// ============================================================
// RideEase – Ride Booking & Fare Estimation Interface
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requirePassenger();

$db = getDB();
$userId = currentUserId();

// Fetch Favorite locations
$favorites = [];
try {
    $favStmt = $db->prepare("SELECT * FROM favorite_locations WHERE user_id = ?");
    $favStmt->execute([$userId]);
    $favorites = $favStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fav locations fetch error: " . $e->getMessage());
}

$pageTitle = "Book a Ride";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="book_ride.php" class="active"><i class="fa-solid fa-map-location-dot"></i> Book Ride</a></li>
            <li><a href="ride_history.php"><i class="fa-solid fa-list-ul"></i> Ride History</a></li>
            <li><a href="profile.php"><i class="fa-solid fa-user-gear"></i> Account Settings</a></li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <div class="dashboard-content">
        <h1 class="gradient-text">Book a New Ride</h1>
        <p class="text-secondary" style="margin-bottom: 2rem;">Select your pickup point and destination. You can click on the map to set coordinates directly.</p>

        <div class="grid-2">
<<<<<<< HEAD

=======
            <!-- Left Side: Interactive Map -->
>>>>>>> origin/main
            <div>
                <div class="card" style="padding:0; overflow:hidden;">
                    <div id="map" style="height: 480px; width: 100%;"></div>
                </div>
            </div>

<<<<<<< HEAD
=======
            <!-- Right Side: Booking Form details -->
>>>>>>> origin/main
            <div>
                <div class="card">
                    <h3 class="gradient-text">Route Details</h3>
                    <form action="../api/book_ride.php" method="POST" id="booking-form">
<<<<<<< HEAD

                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

=======
                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
>>>>>>> origin/main
                        <input type="hidden" name="pickup_coords" id="pickup_coords" required>
                        <input type="hidden" name="dest_coords" id="dest_coords" required>
                        <input type="hidden" name="distance_km" id="distance_km" required>
                        <input type="hidden" name="estimated_fare" id="estimated_fare_val" required>
                        <input type="hidden" name="peak_multiplier" id="peak_multiplier" value="1.00">
                        <input type="hidden" name="discount_amount" id="discount_amount" value="0.00">
                        <input type="hidden" name="coupon_id" id="coupon_id" value="">

                        <!-- Favorite location quick selections -->
                        <?php if (!empty($favorites)): ?>
                            <div class="form-group">
                                <label><i class="fa-solid fa-star" style="color:var(--warning);"></i> Quick Fill from Favorites</label>
                                <select class="form-control" onchange="quickFillLocation(this)">
                                    <option value="">-- Choose Favorite Location --</option>
                                    <?php foreach ($favorites as $fav): ?>
                                        <option value="<?php echo sanitize($fav['address']); ?>"><?php echo sanitize($fav['label'] . ': ' . $fav['address']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="pickup_location">Pickup Address</label>
                            <input type="text" name="pickup_location" id="pickup_location" class="form-control" placeholder="Enter pickup spot" required>
                        </div>

                        <div class="form-group">
                            <label for="destination">Dropoff Destination</label>
                            <input type="text" name="destination" id="destination" class="form-control" placeholder="Enter dropoff location" required>
                        </div>

                        <!-- Coupon Form Input -->
                        <div class="form-group">
                            <label for="coupon_code">Coupon Discount Code</label>
                            <div style="display:flex; gap:10px;">
                                <input type="text" id="coupon_code" class="form-control" placeholder="WELCOME, RIDE10" style="text-transform:uppercase;">
                                <button type="button" class="btn btn-secondary" onclick="applyCouponCode()">Apply</button>
                            </div>
                        </div>

                        <!-- Mode selection -->
                        <div class="form-group">
                            <label for="payment_method">Preferred Checkout Method</label>
                            <select name="payment_method" id="payment_method" class="form-control">
                                <option value="cash">Cash Simulation</option>
                                <option value="bkash">bKash Mobile Wallet</option>
                                <option value="card">Visa / Debit Card</option>
                            </select>
                        </div>

                        <!-- Live estimation outputs -->
                        <div style="background-color: var(--bg-tertiary); padding:1rem; border-radius:8px; border:1px solid var(--border-color); margin-bottom: 1.5rem;">
                            <div style="display:flex; justify-content:space-between; margin-bottom: 5px;">
                                <span class="text-secondary">Estimated Distance:</span>
                                <strong id="distance_display">0.00 km</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between;">
                                <span class="text-secondary">Total Est. Price:</span>
                                <strong id="fare_estimate_display" class="gradient-text" style="font-size:1.2rem;">৳ 0.00</strong>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;">Confirm Booking Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet integration files and local script init -->
<script src="<?php echo BASE_URL; ?>/assets/js/booking.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        initBookingMap();
    });

    function quickFillLocation(select) {
        if (!select.value) return;
        const pickupInput = document.getElementById('pickup_location');
        const destInput = document.getElementById('destination');
        
        if (!pickupInput.value) {
            pickupInput.value = select.value;
            showToast("Set as Pickup Point. Drag map to confirm coordinates.", "info");
        } else {
            destInput.value = select.value;
            showToast("Set as dropoff Point.", "info");
        }
        select.value = ''; // Reset dropdown
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
