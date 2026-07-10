<?php
// ============================================================
// RideEase – API: Submit Driver Rating & Feedback
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requirePassenger();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}














$rideId = isset($_POST['ride_id']) ? intval($_POST['ride_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$feedback = sanitize($_POST['feedback'] ?? '');
$userId = currentUserId();

if (!$rideId || $rating < 1 || $rating > 5) {
    jsonResponse(['success' => false, 'message' => 'Invalid rating values. Must be 1 to 5 stars.'], 400);
}














$db = getDB();

try {
    $db->beginTransaction();

    // Verify ride completes state and customer ownership
    $rideStmt = $db->prepare("SELECT driver_id, status FROM rides WHERE id = ? AND passenger_id = ?");
    $rideStmt->execute([$rideId, $userId]);
    $ride = $rideStmt->fetch();

    if (!$ride || $ride['status'] !== 'completed') {
        throw new Exception("Unable to rate this ride. Trip might not be completed yet.");
    }














    // Insert rating details
    $ratingStmt = $db->prepare("INSERT INTO ratings (ride_id, driver_id, passenger_id, rating, feedback) VALUES (?, ?, ?, ?, ?)");
    $ratingStmt->execute([$rideId, $ride['driver_id'], $userId, $rating, $feedback]);

    // Recalculate Driver Ratings stats
    $statsStmt = $db->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as count_r FROM ratings WHERE driver_id = ?");
    $statsStmt->execute([$ride['driver_id']]);
    $stats = $statsStmt->fetch();

    $updateDriver = $db->prepare("UPDATE drivers SET avg_rating = ?, total_trips = ? WHERE id = ?");
    $updateDriver->execute([$stats['avg_r'], $stats['count_r'], $ride['driver_id']]);

    $db->commit();
    jsonResponse(['success' => true, 'message' => 'Rating feedback successfully logged. Thank you!']);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }













    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}














































































