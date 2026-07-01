<?php
// ============================================================
// RideEase – API: Fare Estimate
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}




$distanceKm = isset($_POST['distance_km']) ? floatval($_POST['distance_km']) : 0.00;

if ($distanceKm <= 0) {
    jsonResponse(['success' => false, 'message' => 'Distance must be greater than zero.'], 400);
}




$db = getDB();

// 1. Calculate default peak hours multiplier
$multiplier = 1.00;
$dayOfWeek = date('w'); // 0=Sunday, 6=Saturday
$currentTime = date('H:i:s');

try {
    $stmt = $db->prepare("
        SELECT multiplier FROM peak_hours 
        WHERE day_of_week = ? AND start_time <= ? AND end_time >= ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$dayOfWeek, $currentTime, $currentTime]);
    $matchedMultiplier = $stmt->fetchColumn();
    
    if ($matchedMultiplier) {
        $multiplier = floatval($matchedMultiplier);
    }



} catch (PDOException $e) {
    error_log("Peak multiplier error: " . $e->getMessage());
}




// 2. Compute Fare Formula
$estimatedFare = (BASE_FARE + ($distanceKm * RATE_PER_KM)) * $multiplier;

jsonResponse([
    'success' => true,
    'estimated_fare' => number_format($estimatedFare, 2, '.', ''),
    'peak_multiplier' => number_format($multiplier, 2, '.', ''),
    'base_fare' => BASE_FARE,
    'rate_per_km' => RATE_PER_KM
]);
  














