<?php
// ============================================================
// RideEase – API: Validate and Apply Coupon Discount Code (Check status, expiration, compute discount & update ride entries)
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requirePassenger();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$code = strtoupper(sanitize($_POST['coupon_code'] ?? ''));
$fare = floatval($_POST['estimated_fare'] ?? 0);

if (empty($code) || $fare <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid inputs provided.'], 400);
}

$db = getDB();

try {
    $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        jsonResponse(['success' => false, 'message' => 'Invalid coupon code.']);
    }

    if ($coupon['is_active'] == 0) {
        jsonResponse(['success' => false, 'message' => 'This coupon has been deactivated.']);
    }

    if (strtotime($coupon['expires_at']) < strtotime(date('Y-m-d'))) {
        jsonResponse(['success' => false, 'message' => 'This coupon has expired.']);
    }

    if ($coupon['used_count'] >= $coupon['max_uses']) {
        jsonResponse(['success' => false, 'message' => 'This coupon usage limit has been reached.']);
    }

    if ($fare < $coupon['min_fare']) {
        jsonResponse(['success' => false, 'message' => 'Minimum fare of BDT ' . $coupon['min_fare'] . ' is required.']);
    }

    // Compute Discount
    $discount = 0.00;
    if ($coupon['discount_type'] === 'percent') {
        $discount = $fare * ($coupon['discount_value'] / 100);
    } else {
        $discount = floatval($coupon['discount_value']);
    }

    // Limit discount to not exceed fare
    $discount = min($discount, $fare);

    jsonResponse([
        'success' => true,
        'coupon_id' => $coupon['id'],
        'discount' => number_format($discount, 2, '.', ''),
        'message' => 'Coupon code applied successfully!'
    ]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Database process failed.'], 500);
}
