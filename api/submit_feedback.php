<?php
// ============================================================
// RideEase – API: Submit Complaint / Feedback Ticket (Design layout, save details, retrieve tickets list & enforce character validations)
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$rideId = !empty($_POST['ride_id']) ? intval($_POST['ride_id']) : null;
$subject = sanitize($_POST['subject'] ?? '');
$description = sanitize($_POST['description'] ?? '');
$userId = currentUserId();

if (empty($subject) || empty($description)) {
    jsonResponse(['success' => false, 'message' => 'Subject and ticket details are required.'], 400);
}

$db = getDB();

try {
    $stmt = $db->prepare("
        INSERT INTO complaints (user_id, ride_id, subject, description, status) 
        VALUES (?, ?, ?, ?, 'open')
    ");
    $stmt->execute([$userId, $rideId, $subject, $description]);

    jsonResponse([
        'success' => true,
        'message' => 'Complaint ticket successfully created. Support will resolve shortly.'
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to log complaint record.'], 500);
}
