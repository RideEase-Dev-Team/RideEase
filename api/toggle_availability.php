<?php
// ============================================================
// RideEase – API: Toggle Driver Availability
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireDriver();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}



$status = isset($_POST['is_available']) ? intval($_POST['is_available']) : 0;
$userId = currentUserId();

$db = getDB();

try {
    // Verify driver registration approval state first
    $driverStmt = $db->prepare("SELECT id, is_approved, is_suspended FROM drivers WHERE user_id = ? LIMIT 1");
    $driverStmt->execute([$userId]);
    $driver = $driverStmt->fetch();

    if (!$driver) {
        jsonResponse(['success' => false, 'message' => 'Driver profile not found.'], 404);
    }



    if (!$driver['is_approved']) {
        jsonResponse(['success' => false, 'message' => 'Your application is pending admin approval. You cannot go online yet.']);
    }



    if ($driver['is_suspended']) {
        jsonResponse(['success' => false, 'message' => 'Your account is suspended. Check with administrator.']);
    }



    // Toggle Availability
    $stmt = $db->prepare("UPDATE drivers SET is_available = ? WHERE id = ?");
    $stmt->execute([$status, $driver['id']]);

    jsonResponse([
        'success' => true,
        'message' => $status ? 'You are now online. Listening for ride requests!' : 'You are now offline.'
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to update availability status.'], 500);
}












