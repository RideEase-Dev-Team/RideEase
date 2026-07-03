<?php
// ============================================================
// RideEase – API: Cancel Ride Booking
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}



$rideId = isset($_POST['ride_id']) ? intval($_POST['ride_id']) : 0;
$reason = sanitize($_POST['reason'] ?? 'Cancelled via user action');
$role = currentUserRole();
$userId = currentUserId();

if (!$rideId) {
    jsonResponse(['success' => false, 'message' => 'Ride ID is required.'], 400);
}



$db = getDB();

try {
    $db->beginTransaction();

    // Fetch ride details
    $rideStmt = $db->prepare("SELECT passenger_id, driver_id, status FROM rides WHERE id = ? FOR UPDATE");
    $rideStmt->execute([$rideId]);
    $ride = $rideStmt->fetch();

    if (!$ride) {
        throw new Exception("Ride not found.");
    }



    if (in_array($ride['status'], ['completed', 'cancelled'])) {
        throw new Exception("Cannot cancel a completed or already cancelled ride.");
    }



    // Update status
    $updateStmt = $db->prepare("UPDATE rides SET status = 'cancelled' WHERE id = ?");
    $updateStmt->execute([$rideId]);

    // Insert cancellation log
    $logStmt = $db->prepare("INSERT INTO cancellations (ride_id, cancelled_by, cancelled_by_role, reason) VALUES (?, ?, ?, ?)");
    $logStmt->execute([$rideId, $userId, $role, $reason]);

    // Re-enable driver availability if one was assigned
    if ($ride['driver_id']) {
        $driverToggle = $db->prepare("UPDATE drivers SET is_available = 1 WHERE id = ?");
        $driverToggle->execute([$ride['driver_id']]);
    }



    $db->commit();
    jsonResponse(['success' => true, 'message' => 'Ride successfully cancelled.']);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }


    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}












