<?php
// ============================================================
// RideEase – API: Driver Reject Ride Request
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireDriver();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}


$rideId = isset($_POST['ride_id']) ? intval($_POST['ride_id']) : 0;

if (!$rideId) {
    jsonResponse(['success' => false, 'message' => 'Ride ID is required.'], 400);
}


// Simulating rejection logs: Driver skips assignment and matches next.
jsonResponse([
    'success' => true,
    'message' => 'Ride request successfully skipped. Monitoring for new bookings.'
]);





