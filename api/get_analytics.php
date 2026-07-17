<?php
// ============================================================
// RideEase – API: Fetch Platform Analytics (Admin) (Design JSON response schema for dashboard graphs)
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireAdmin();

$db = getDB();

try {
    // 1. Daily revenue of the last 7 days
    $revStmt = $db->query("
        SELECT DATE_FORMAT(created_at, '%b %d') as label, SUM(amount) as value 
        FROM payments 
        WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC
    ");
    $revenueStats = $revStmt->fetchAll();

    // 2. Rides status counts
    $statusStmt = $db->query("
        SELECT status, COUNT(*) as count 
        FROM rides 
        GROUP BY status
    ");
    $rideStats = $statusStmt->fetchAll();

    jsonResponse([
        'success' => true,
        'revenue' => $revenueStats,
        'rides' => $rideStats
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Failed to load analytics: ' . $e->getMessage()], 500);
}
