<?php
// ============================================================
// RideEase – Auth check module
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Helper to check user activation status in real-time
function verifyUserActiveState(): void {
    if (isLoggedIn()) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT is_active FROM users WHERE id = ?");
            $stmt->execute([currentUserId()]);
            $user = $stmt->fetch();
            
            if (!$user || $user['is_active'] == 0) {
                // Force logout deactivated users
                session_unset();
                session_destroy();
                redirect('/auth/login.php?msg=Your+account+has+been+deactivated+or+suspended');
            }
        } catch (PDOException $e) {
            // Ignore DB errors during state checks to avoid loop crash, but log it
            error_log($e->getMessage());
        }
    }
}

// Call check on inclusion
verifyUserActiveState();
