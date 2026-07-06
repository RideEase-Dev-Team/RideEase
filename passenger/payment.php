<?php
/*
|--------------------------------------------------------------------------
| RideEase - Passenger Payment Module
|--------------------------------------------------------------------------
| This section handles the backend logic for the passenger payment page.
| It validates the ride, prevents duplicate payments, processes the
| simulated payment, records driver earnings, and ensures secure
| transactions using CSRF protection and database transactions.
|--------------------------------------------------------------------------
*/

// Include database connection and session management files.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Allow access only to authenticated passengers.
requirePassenger();

// Create a database connection and retrieve the logged-in passenger ID.
$db = getDB();
$userId = currentUserId();

// Retrieve the ride ID from the URL.
$rideId = isset($_GET['ride_id']) ? intval($_GET['ride_id']) : 0;

// Validate the ride ID before proceeding.
if (!$rideId) {
    setFlash('danger', "Invalid ride ID for checkout.");
    redirect('/passenger/dashboard.php');
}

try {
    // Retrieve the completed ride that belongs to the current passenger.
    $stmt = $db->prepare("SELECT * FROM rides WHERE id = ? AND passenger_id = ? AND status = 'completed'");
    $stmt->execute([$rideId, $userId]);
    $ride = $stmt->fetch();

    // Stop execution if the ride does not exist or does not belong to the passenger.
    if (!$ride) {
        setFlash('danger', "Completed ride not found or access denied.");
        redirect('/passenger/dashboard.php');
    }

    // Check whether the ride has already been paid.
    $payStmt = $db->prepare("SELECT status FROM payments WHERE ride_id = ?");
    $payStmt->execute([$rideId]);
    $paidStatus = $payStmt->fetchColumn();

    // Prevent duplicate payment for the same ride.
    if ($paidStatus === 'completed') {
        setFlash('warning', "This ride has already been paid.");
        redirect("/passenger/track_ride.php?ride_id=" . $rideId);
    }
} catch (PDOException $e) {
    // Handle database errors while loading ride information.
    setFlash('danger', "Database error loading checkout details.");
    redirect('/passenger/dashboard.php');
}

// Process the payment when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify the CSRF token to protect against forged requests.
    verifyCsrf();

    // Retrieve and sanitize the selected payment method.
    $method = sanitize($_POST['payment_method']);

    // Generate a simulated transaction reference.
    $txnRef = 'TXN-' . strtoupper($method) . '-' . rand(10000, 99999);

    try {
        // Start a database transaction to ensure data consistency.
        $db->beginTransaction();

        // Record the completed payment.
        $payInsert = $db->prepare("
            INSERT INTO payments (ride_id, amount, method, status, transaction_id)
            VALUES (?, ?, ?, 'completed', ?)
        ");
        $payInsert->execute([
            $rideId,
            $ride['final_fare'],
            $method,
            $txnRef
        ]);

        // Calculate and store the driver's earnings if a driver is assigned.
        if ($ride['driver_id']) {

            $gross = $ride['final_fare'];

            // Platform commission percentage.
            $commPct = PLATFORM_COMMISSION;

            // Calculate commission and driver's net earnings.
            $commission = $gross * ($commPct / 100);
            $net = $gross - $commission;

            // Insert the driver's earnings into the database.
            $earnInsert = $db->prepare("
                INSERT INTO driver_earnings
                (driver_id, ride_id, gross_amount, commission_pct, commission_amount, net_amount)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $earnInsert->execute([
                $ride['driver_id'],
                $ride['id'],
                $gross,
                $commPct,
                $commission,
                $net
            ]);
        }

        // Save all changes to the database.
        $db->commit();

        // Display a success message and redirect to the ride tracking page.
        setFlash(
            'success',
            "Simulated payment of " .
            formatBDT($ride['final_fare']) .
            " completed via " .
            strtoupper($method)
        );

        redirect("/passenger/track_ride.php?ride_id=" . $rideId);

    } catch (PDOException $e) {

        // Roll back all database changes if an error occurs.
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        // Display an error message to the passenger.
        setFlash('danger', "Checkout simulation failed: " . $e->getMessage());
    }
}

$pageTitle = "Settle Ride Payment";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper" style="min-height: 80vh;">
    <div class="auth-card" style="max-width: 500px;">
        <h2 class="text-center gradient-text">Secure Settle Payment</h2>
        <p class="text-center text-muted" style="margin-bottom: 2rem;">Simulating university sandbox payment gateway</p>

        <div style="background-color:var(--bg-tertiary); padding: 1.2rem; border-radius: 8px; border:1px solid var(--border-color); margin-bottom: 1.5rem;">
            <div style="display:flex; justify-content:space-between; margin-bottom: 5px;">
                <span class="text-secondary">Pickup Location:</span>
                <strong style="text-align:right; font-size:0.9rem;"><?php echo sanitize($ride['pickup_location']); ?></strong>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom: 5px;">
                <span class="text-secondary">Destination Point:</span>
                <strong style="text-align:right; font-size:0.9rem;"><?php echo sanitize($ride['destination']); ?></strong>
            </div>
            <div style="display:flex; justify-content:space-between; border-top:1px solid var(--border-color); padding-top:10px; margin-top:10px;">
                <span class="text-secondary">Total Amount Due:</span>
                <strong class="gradient-text" style="font-size:1.3rem;"><?php echo formatBDT($ride['final_fare']); ?></strong>
            </div>
        </div>

        <form action="payment.php?ride_id=<?php echo $rideId; ?>" method="POST" id="checkout-sim-form">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            
            <div class="form-group">
                <label for="payment_method">Select Payment Channel</label>
                <select name="payment_method" id="payment_method" class="form-control" onchange="switchGatewayUI(this.value)">
                    <option value="cash">Cash Handover</option>
                    <option value="bkash">bKash Account Wallet</option>
                    <option value="card">Visa/Mastercard Checkout</option>
                </select>
            </div>

            <div id="bkash-sim-fields" style="display:none; border: 1px solid #E2125B; padding: 1rem; border-radius: 8px; background: rgba(226, 18, 91, 0.05); margin-bottom: 1.5rem; animation: slideDown 0.3s ease;">
                <div style="text-align:center; margin-bottom:1rem;">
                    <strong style="color:#E2125B; font-size: 1.1rem;">bKash Payment Gateway Sandbox</strong>
                </div>
                <div class="form-group">
                    <label style="color:#E2125B;">bKash Mobile No.</label>
                    <input type="text" class="form-control" placeholder="017xxxxxxxx" style="border-color:#E2125B;">
                </div>
                <div class="form-group">
                    <label style="color:#E2125B;">PIN Code</label>
                    <input type="password" class="form-control" placeholder="••••" style="border-color:#E2125B;">
                </div>
            </div>

            <div id="card-sim-fields" style="display:none; border: 1px solid var(--accent-cyan); padding: 1rem; border-radius: 8px; background: rgba(0, 240, 255, 0.05); margin-bottom: 1.5rem; animation: slideDown 0.3s ease;">
                <div style="text-align:center; margin-bottom:1rem;">
                    <strong style="color:var(--accent-cyan); font-size: 1.1rem;">Visa / Mastercard Gateway Sandbox</strong>
                </div>
                <div class="form-group">
                    <label>Cardholder Name</label>
                    <input type="text" class="form-control" placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" class="form-control" placeholder="4111 2222 3333 4444">
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="text" class="form-control" placeholder="MM/YY">
                    </div>
                    <div class="form-group">
                        <label>CVV</label>
                        <input type="password" class="form-control" placeholder="•••">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success" style="width:100%; margin-top:1rem;"><i class="fa-solid fa-circle-check"></i> Settle simulated payout</button>
        </form>
    </div>
</div>

<script>
    function switchGatewayUI(val) {
        const bkash = document.getElementById('bkash-sim-fields');
        const card = document.getElementById('card-sim-fields');
        
        bkash.style.display = 'none';
        card.style.display = 'none';
        
        if (val === 'bkash') {
            bkash.style.display = 'block';
        } else if (val === 'card') {
            card.style.display = 'block';
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
