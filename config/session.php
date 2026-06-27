<?php
// ============================================================
// RideEase – Session Management & Helpers
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,    // 24 hours
        'path'     => '/',
        'secure'   => false,    // Set true in production (HTTPS)
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// ── Auth helpers ──────────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function currentUserRole(): ?string {
    return $_SESSION['role'] ?? null;
}

function currentUserName(): ?string {
    return $_SESSION['name'] ?? null;
}

function isAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function isDriver(): bool {
    return ($_SESSION['role'] ?? '') === 'driver';
}

function isPassenger(): bool {
    return ($_SESSION['role'] ?? '') === 'passenger';
}

// ── Redirect helpers ──────────────────────────────────────────

function redirect(string $path): void {
    header('Location: ' . BASE_URL . $path);
    exit;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('/auth/login.php?msg=Please+login+to+continue');
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array(currentUserRole(), $roles)) {
        redirect('/auth/login.php?msg=Access+denied');
    }
}

function requireAdmin(): void {
    requireRole('admin');
}

function requireDriver(): void {
    requireRole('driver');
}

function requirePassenger(): void {
    requireRole('passenger');
}

function redirectIfLoggedIn(): void {
    if (!isLoggedIn()) return;
    $role = currentUserRole();
    if ($role === 'admin')     redirect('/admin/dashboard.php');
    if ($role === 'driver')    redirect('/driver/dashboard.php');
    if ($role === 'passenger') redirect('/passenger/dashboard.php');
}

// ── Flash messages ────────────────────────────────────────────

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ── CSRF ──────────────────────────────────────────────────────

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

// ── Utility ───────────────────────────────────────────────────

function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function formatBDT(float $amount): string {
    return '৳ ' . number_format($amount, 2);
}

function timeAgo(string $datetime): string {
    $time = time() - strtotime($datetime);
    if ($time < 60)     return 'Just now';
    if ($time < 3600)   return floor($time / 60) . ' min ago';
    if ($time < 86400)  return floor($time / 3600) . ' hr ago';
    return floor($time / 86400) . ' days ago';
}
  