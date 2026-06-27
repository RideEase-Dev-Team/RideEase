<?php
// ============================================================
// RideEase – Login Page
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

redirectIfLoggedIn();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all credentials.";
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['is_active'] == 0) {
                    $error = "Your account has been deactivated. Please contact support.";
                } else {
                    // Start session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];

                    setFlash('success', "Welcome back, " . $user['name'] . "!");
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        redirect('/admin/dashboard.php');
                    } elseif ($user['role'] === 'driver') {
                        redirect('/driver/dashboard.php');
                    } else {
                        redirect('/passenger/dashboard.php');
                    }
                }
            } else {
                $error = "Incorrect email address or password.";
            }
        } catch (PDOException $e) {
            $error = "Authentication failed. Database error.";
        }
    }
}

$pageTitle = "Login";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h2 class="text-center gradient-text">Sign In</h2>
        <p class="text-center text-muted" style="margin-bottom: 2rem;">Access your RideEase account portal</p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?php echo sanitize($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-warning">
                <i class="fa-solid fa-circle-info"></i>
                <span><?php echo sanitize($_GET['msg']); ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="name@domain.com" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                    <label for="password" style="margin-bottom:0;">Password</label>
                    <a href="forgot_password.php" style="font-size:0.8rem; color: var(--accent-cyan); text-decoration:none;">Forgot Password?</a>
                </div>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem;">Login Portal</button>
        </form>

        <div style="margin-top: 1.5rem; text-align:center; font-size:0.9rem;" class="text-secondary">
            Need a passenger account? <a href="register.php" style="color: var(--accent-cyan); text-decoration:none;">Register here</a>
        </div>
        <div style="margin-top: 0.5rem; text-align:center; font-size:0.9rem;" class="text-secondary">
            Are you a driver? <a href="driver_register.php" style="color: var(--accent-purple); text-decoration:none;">Join as Partner</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 

// End of Login Page
