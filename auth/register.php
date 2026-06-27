<?php
// ============================================================
// RideEase – Passenger Registration Page
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

redirectIfLoggedIn();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = "Please fill in all required inputs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            $db = getDB();
            
            // Check if email already registered
            $check = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = "This email address is already in use.";
            } else {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                
                $stmt = $db->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'passenger')");
                $stmt->execute([$name, $email, $phone, $passwordHash]);
                $newUserId = $db->lastInsertId();

                $_SESSION['user_id'] = $newUserId;
                $_SESSION['role'] = 'passenger';
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;

                setFlash('success', "Welcome to RideEase! Registration complete.");
                redirect('/passenger/dashboard.php');
            }
        } catch (PDOException $e) {
            $error = "Registration failed. Server database error.";
        }
    }
}

$pageTitle = "Passenger Registration";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h2 class="text-center gradient-text">Create Account</h2>
        <p class="text-center text-muted" style="margin-bottom: 2rem;">Register as a Passenger rider</p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?php echo sanitize($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="John Doe" required value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="john@example.com" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" name="phone" id="phone" class="form-control" placeholder="017xxxxxxxx" required value="<?php echo isset($_POST['phone']) ? sanitize($_POST['phone']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem;">Register Now</button>
        </form>

        <div style="margin-top: 1.5rem; text-align:center; font-size:0.9rem;" class="text-secondary">
            Already have an account? <a href="login.php" style="color: var(--accent-cyan); text-decoration:none;">Log In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
