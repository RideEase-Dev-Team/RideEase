<?php
// ============================================================
// RideEase – API: Book Ride & Driver Auto-Match Assignment
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requirePassenger();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('danger', 'Invalid request method.');
    redirect('/passenger/book_ride.php');
}




















verifyCsrf();

$passengerId = currentUserId();
$pickup = sanitize($_POST['pickup_location'] ?? '');
$dest = sanitize($_POST['destination'] ?? '');
$distance = floatval($_POST['distance_km'] ?? 0);
$estFare = floatval($_POST['estimated_fare'] ?? 0);
$multiplier = floatval($_POST['peak_multiplier'] ?? 1.00);
$discount = floatval($_POST['discount_amount'] ?? 0.00);
$couponId = !empty($_POST['coupon_id']) ? intval($_POST['coupon_id']) : null;
$payMethod = sanitize($_POST['payment_method'] ?? 'cash');

// Final Fare Deduction
$finalFare = max(0, $estFare - $discount);

if (empty($pickup) || empty($dest) || $distance <= 0) {
    setFlash('danger', 'Please verify route points on map.');
    redirect('/passenger/book_ride.php');
}




















$db = getDB();

try {
    $db->beginTransaction();

    // 1. Insert pending ride booking
    $stmt = $db->prepare("
        INSERT INTO rides (passenger_id, pickup_location, destination, distance_km, base_fare, peak_multiplier, discount_amount, final_fare, status, payment_method, coupon_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
    ");
    $stmt->execute([$passengerId, $pickup, $dest, $distance, BASE_FARE, $multiplier, $discount, $finalFare, $payMethod, $couponId]);
    $rideId = $db->lastInsertId();

    // 2. Fetch available verified driver to auto assign
    $driverQuery = $db->query("
        SELECT id FROM drivers 
        WHERE is_available = 1 AND is_approved = 1 AND is_suspended = 0 
        LIMIT 1
    ");
    $driverId = $driverQuery->fetchColumn();

    if ($driverId) {
        // Auto assign driver
        $assignStmt = $db->prepare("
            UPDATE rides 
            SET driver_id = ?, status = 'assigned', assigned_at = NOW() 
            WHERE id = ?
        ");
        $assignStmt->execute([$driverId, $rideId]);

        // Toggle driver availability off while in ride
        $driverToggle = $db->prepare("UPDATE drivers SET is_available = 0 WHERE id = ?");
        $driverToggle->execute([$driverId]);
        
        setFlash('success', "Ride booked! Driver successfully matched.");
    } else {
        // Leave pending status (driver poll page will resolve)
        setFlash('warning', "Booking requested! Searching for available drivers...");
    }




















    $db->commit();
    redirect("/passenger/track_ride.php?ride_id=" . $rideId);
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }



















    setFlash('danger', "Booking process failed: " . $e->getMessage());
    redirect('/passenger/book_ride.php');
}



















      






























































































