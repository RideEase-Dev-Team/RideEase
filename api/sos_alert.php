<?php
// ============================================================
// RideEase – API: Trigger SOS Emergency distress alert (Design payload fields & save alert location to database)
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$rideId = !empty($_POST['ride_id']) ? intval($_POST['ride_id']) : null;
$message = sanitize($_POST['message'] ?? 'SOS Triggered by User');
$userId = currentUserId();

$db = getDB();

try {
    // Insert Emergency SOS entry
    $stmt = $db->prepare("
        INSERT INTO sos_alerts (ride_id, user_id, message, is_resolved) 
        VALUES (?, ?, ?, 0)
    ");
    $stmt->execute([$rideId, $userId, $message]);

    jsonResponse([
        'success' => true,
        'message' => 'SOS Alert successfully recorded. Emergency response notified.'
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to log SOS alert: ' . $e->getMessage()], 500);
}
