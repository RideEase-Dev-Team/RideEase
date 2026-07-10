<?php
// ============================================================
// RideEase – API: Fetch Passenger Favorite Locations (Design JSON payload endpoints & save details to database)
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requirePassenger();

$userId = currentUserId();
$db = getDB();

try {
    $stmt = $db->prepare("SELECT id, label, address FROM favorite_locations WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$userId]);
    $favorites = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'favorites' => $favorites
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to load favorite locations.'], 500);
}
