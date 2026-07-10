<?php
// ============================================================
// RideEase – Driver Registration Page
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

redirectIfLoggedIn();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // Personal details
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Driver Details
    $license_no = sanitize($_POST['license_no']);
    $nid_no = sanitize($_POST['nid_no']);
    $experience_years = intval($_POST['experience_years']);
    
    // Vehicle details
    $vehicle_make = sanitize($_POST['vehicle_make']);
    $vehicle_model = sanitize($_POST['vehicle_model']);
    $vehicle_year = intval($_POST['vehicle_year']);
    $vehicle_color = sanitize($_POST['vehicle_color']);
    $vehicle_plate = sanitize($_POST['vehicle_plate']);
    $vehicle_type = sanitize($_POST['vehicle_type']);

    if (
        empty($name) || empty($email) || empty($phone) || empty($password) ||
        empty($license_no) || empty($nid_no) || empty($vehicle_make) || 
        empty($vehicle_model) || empty($vehicle_plate)
    ) {
        $error = "Please fill in all inputs.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        try {
            $db = getDB();
            
            // Check email uniqueness
            $check = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = "This email address is already in use.";
            } else {
                $db->beginTransaction();
                
                // Create user
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'driver')");
                $stmt->execute([$name, $email, $phone, $passwordHash]);
                $userId = $db->lastInsertId();

                // Create driver profile (Default: is_approved = 0)
                $driverStmt = $db->prepare("INSERT INTO drivers (user_id, license_no, nid_no, experience_years, is_available, is_approved) VALUES (?, ?, ?, ?, 0, 0)");
                $driverStmt->execute([$userId, $license_no, $nid_no, $experience_years]);
                $driverId = $db->lastInsertId();

                // Create vehicle profile (Default: is_approved = 0)
                $vehicleStmt = $db->prepare("INSERT INTO vehicles (driver_id, make, model, year, color, plate_no, vehicle_type, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
                $vehicleStmt->execute([$driverId, $vehicle_make, $vehicle_model, $vehicle_year, $vehicle_color, $vehicle_plate, $vehicle_type]);

                $db->commit();
                $success = "Registration complete! Please wait for admin approval before logging in.";
            }
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}

$pageTitle = "Driver Registration";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper" style="min-height: 100vh;">
    <div class="auth-card" style="max-width: 600px;">
        <h2 class="text-center gradient-text">Join as Driver Partner</h2>
        <p class="text-center text-muted" style="margin-bottom: 2rem;">Register driver and vehicle credentials for verification</p>

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
            <div class="text-center" style="margin-top:1.5rem;">
                <a href="login.php" class="btn btn-primary">Go to Login</a>
            </div>
        <?php else: ?>

        <form action="driver_register.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            
            <h3 class="gradient-text" style="font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 5px; margin-bottom: 1rem;"><i class="fa-solid fa-user"></i> Personal Credentials</h3>
            
            <div class="grid-2">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" id="name" class="form-control" required value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" name="phone" id="phone" class="form-control" required value="<?php echo isset($_POST['phone']) ? sanitize($_POST['phone']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="experience_years">Years of Experience</label>
                    <input type="number" name="experience_years" id="experience_years" class="form-control" min="0" required value="<?php echo isset($_POST['experience_years']) ? intval($_POST['experience_years']) : ''; ?>">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="license_no">Driving License No.</label>
                    <input type="text" name="license_no" id="license_no" class="form-control" required value="<?php echo isset($_POST['license_no']) ? sanitize($_POST['license_no']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="nid_no">NID Card Number</label>
                    <input type="text" name="nid_no" id="nid_no" class="form-control" required value="<?php echo isset($_POST['nid_no']) ? sanitize($_POST['nid_no']) : ''; ?>">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                </div>
            </div>

            <h3 class="gradient-text" style="font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 5px; margin-top: 1.5rem; margin-bottom: 1rem;"><i class="fa-solid fa-car"></i> Vehicle Specifications</h3>

            <div class="grid-3">
                <div class="form-group">
                    <label for="vehicle_make">Brand/Make</label>
                    <input type="text" name="vehicle_make" id="vehicle_make" class="form-control" placeholder="Toyota, Honda" required value="<?php echo isset($_POST['vehicle_make']) ? sanitize($_POST['vehicle_make']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="vehicle_model">Model</label>
                    <input type="text" name="vehicle_model" id="vehicle_model" class="form-control" placeholder="Premio, Civic" required value="<?php echo isset($_POST['vehicle_model']) ? sanitize($_POST['vehicle_model']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="vehicle_year">Manufacturing Year</label>
                    <input type="number" name="vehicle_year" id="vehicle_year" class="form-control" min="1990" max="<?php echo date('Y')+1; ?>" required value="<?php echo isset($_POST['vehicle_year']) ? intval($_POST['vehicle_year']) : date('Y'); ?>">
                </div>
            </div>

            <div class="grid-3">
                <div class="form-group">
                    <label for="vehicle_color">Vehicle Color</label>
                    <input type="text" name="vehicle_color" id="vehicle_color" class="form-control" placeholder="Black" required value="<?php echo isset($_POST['vehicle_color']) ? sanitize($_POST['vehicle_color']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="vehicle_plate">License Plate Number</label>
                    <input type="text" name="vehicle_plate" id="vehicle_plate" class="form-control" placeholder="DHAKA-METRO-KA-1122" required value="<?php echo isset($_POST['vehicle_plate']) ? sanitize($_POST['vehicle_plate']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="vehicle_type">Vehicle Type</label>
                    <select name="vehicle_type" id="vehicle_type" class="form-control">
                        <option value="car">Car (Premium)</option>
                        <option value="CNG">CNG Three-Wheeler</option>
                        <option value="motorcycle">Motorcycle</option>
                        <option value="microbus">Microbus</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1.5rem; color:#000;">Submit Application</button>
        </form>

        <div style="margin-top: 1.5rem; text-align:center; font-size:0.9rem;" class="text-secondary">
            Already registered? <a href="login.php" style="color: var(--accent-cyan); text-decoration:none;">Log In</a>
        </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
