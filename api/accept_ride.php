<?php
// ============================================================
// RideEase – API: Driver Accept Ride Request / Polling
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireDriver();

$db = getDB();
$userId = currentUserId();

// Fetch driver details
$driverQuery = $db->prepare("SELECT id, is_approved, is_suspended, is_available FROM drivers WHERE user_id = ? LIMIT 1");
$driverQuery->execute([$userId]);
$driver = $driverQuery->fetch();

if (!$driver || !$driver['is_approved'] || $driver['is_suspended']) {
    jsonResponse(['success' => false, 'message' => 'Driver not verified or suspended.'], 403);
}


// 1. GET Polling check
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check'])) {
    if (!$driver['is_available']) {
        jsonResponse(['success' => false, 'message' => 'Driver offline.']);
    }

    
    try {
        // Find first pending ride with no driver matched
        $stmt = $db->query("
            SELECT r.*, u.name as passenger_name 
            FROM rides r
            JOIN users u ON r.passenger_id = u.id
            WHERE r.status = 'pending' AND r.driver_id IS NULL 
            ORDER BY r.id ASC LIMIT 1
        ");
        $ride = $stmt->fetch();
        
        if ($ride) {
            jsonResponse(['success' => true, 'ride' => $ride]);
        } else {
            jsonResponse(['success' => false, 'message' => 'No active bookings matching.']);
        }

    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Database query failed.'], 500);
    }

}


// 2. POST Accept Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rideId = isset($_POST['ride_id']) ? intval($_POST['ride_id']) : 0;
    
    if (!$rideId) {
        jsonResponse(['success' => false, 'message' => 'Ride ID is required.'], 400);
    }

    
    try {
        $db->beginTransaction();
        
        // Double check ride is still pending
        $checkStmt = $db->prepare("SELECT status FROM rides WHERE id = ? FOR UPDATE");
        $checkStmt->execute([$rideId]);
        $status = $checkStmt->fetchColumn();
        
        if ($status !== 'pending') {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'Ride has already been matched or cancelled.']);
        }

        
        // Accept and assign driver
        $assignStmt = $db->prepare("
            UPDATE rides 
            SET driver_id = ?, status = 'assigned', assigned_at = NOW() 
            WHERE id = ?
        ");
        $assignStmt->execute([$driver['id'], $rideId]);
        
        // Set driver unavailable while on ride
        $driverToggle = $db->prepare("UPDATE drivers SET is_available = 0 WHERE id = ?");
        $driverToggle->execute([$driver['id']]);
        
        $db->commit();
        jsonResponse(['success' => true, 'message' => 'Ride request successfully accepted!']);
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        jsonResponse(['success' => false, 'message' => 'Accept failed: ' . $e->getMessage()], 500);
    }

}






