<?php
// ============================================================
// RideEase – Forgot Password Simulation
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';


redirectIfLoggedIn();

$error = null;
$success = null;
$step = 1;
$reset_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (isset($_POST['request_reset'])) {
        $email = sanitize($_POST['email']);
        
        if (empty($email)) {
            $error = "Please provide your email address.";
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    // Simulation shortcut: Provide mock token directly on screen
                    $step = 2;
                    $reset_email = $email;
                    setFlash('success', "Verification Code Simulated! Enter a new password.");
                } else {
                    $error = "Email address not found.";
                }
            } catch (PDOException $e) {
                $error = "Database search error.";
            }
        }
    } elseif (isset($_POST['reset_password'])) {
        $email = sanitize($_POST['email']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($new_password) || empty($confirm_password)) {
            $error = "Please fill in all inputs.";
            $step = 2;
            $reset_email = $email;
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters.";
            $step = 2;
            $reset_email = $email;
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
            $step = 2;
            $reset_email = $email;
        } else {
            try {
                $db = getDB();
                $hash = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
                $stmt->execute([$hash, $email]);

                $success = "Password successfully reset! You can now log in.";
                $step = 3;
            } catch (PDOException $e) {
                $error = "Failed to update password.";
            }
        }
    }
}

$pageTitle = "Forgot Password";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h2 class="text-center gradient-text">Reset Password</h2>
        <p class="text-center text-muted" style="margin-bottom: 2rem;">Simulated password recovery workflow</p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?php echo sanitize($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo sanitize($success); ?></span>
            </div>
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="login.php" class="btn btn-primary">Go to Login</a>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <form action="forgot_password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                
                <div class="form-group">
                    <label for="email">Account Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="user@rideease.com" required>
                </div>

                <button type="submit" name="request_reset" class="btn btn-primary" style="width:100%; margin-top:1rem;">Send Reset Code</button>
            </form>
        <?php elseif ($step === 2): ?>
            <form action="forgot_password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="email" value="<?php echo sanitize($reset_email); ?>">
                
                <div class="form-group">
                    <label>Mock Security Code (Simulated auto-fill)</label>
                    <input type="text" class="form-control" value="MOCK-RESET-CODE-8823" disabled style="background-color: var(--bg-primary); border-style: dashed;">
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" name="reset_password" class="btn btn-primary" style="width:100%; margin-top:1rem;">Update Password</button>
            </form>
        <?php endif; ?>

        <?php if ($step !== 3): ?>
        <div style="margin-top: 1.5rem; text-align:center; font-size:0.9rem;" class="text-secondary">
            Remembered your credentials? <a href="login.php" style="color: var(--accent-cyan); text-decoration:none;">Log In</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
