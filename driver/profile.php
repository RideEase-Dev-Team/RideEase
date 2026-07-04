<?php
// ============================================================
// RideEase – Driver Profile & Vehicle Details Settings
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireDriver();

$db = getDB();
$userId = currentUserId();

// Handle profile updates
if (isset($_POST['update_profile'])) {
    verifyCsrf();
    
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);

    if (empty($name) || empty($phone)) {
        setFlash('danger', "All profile input details are required.");
    } else {
        try {
            $stmt = $db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $userId]);
            
            $_SESSION['name'] = $name; // Update session
            setFlash('success', "Personal details successfully saved.");
            redirect('/driver/profile.php');
        } catch (PDOException $e) {
            setFlash('danger', "Failed to update profile details.");
        }







    }







}








// Fetch driver details + vehicle details
try {
    $driverStmt = $db->prepare("
        SELECT d.*, u.name, u.email, u.phone 
        FROM drivers d 
        JOIN users u ON d.user_id = u.id 
        WHERE d.user_id = ?
    ");
    $driverStmt->execute([$userId]);
    $driver = $driverStmt->fetch();

    $vehicleStmt = $db->prepare("SELECT * FROM vehicles WHERE driver_id = ? LIMIT 1");
    $vehicleStmt->execute([$driver['id']]);
    $vehicle = $vehicleStmt->fetch();
} catch (PDOException $e) {
    die("Database fetch error for profile fields.");
}








$pageTitle = "Driver Profile Settings";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Driver Hub</a></li>
            <li><a href="earnings.php"><i class="fa-solid fa-wallet"></i> My Earnings</a></li>
            <li><a href="ride_history.php"><i class="fa-solid fa-list-ul"></i> Ride History</a></li>
            <li><a href="profile.php" class="active"><i class="fa-solid fa-user-gear"></i> Profile Settings</a></li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <div class="dashboard-content">
        <h1 class="gradient-text">Profile & Vehicle Settings</h1>
        <p class="text-secondary" style="margin-bottom: 2rem;">Manage credentials and verify linked cabs</p>

        <div class="grid-2">
            <!-- Left Side: Profile Details form -->
            <div class="card">
                <h3>Personal Driver Details</h3>
                <form action="profile.php" method="POST" style="margin-top:1rem;">
                    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                    
                    <div class="form-group">
                        <label>Account Email (Read Only)</label>
                        <input type="text" class="form-control" value="<?php echo sanitize($driver['email']); ?>" disabled style="background-color: var(--bg-tertiary);">
                    </div>

                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" required value="<?php echo sanitize($driver['name']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="form-control" required value="<?php echo sanitize($driver['phone']); ?>">
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>License No.</label>
                            <input type="text" class="form-control" value="<?php echo sanitize($driver['license_no']); ?>" disabled style="background-color: var(--bg-tertiary);">
                        </div>
                        <div class="form-group">
                            <label>NID Card No.</label>
                            <input type="text" class="form-control" value="<?php echo sanitize($driver['nid_no']); ?>" disabled style="background-color: var(--bg-tertiary);">
                        </div>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary" style="width:100%; margin-top:1rem;">Save Settings</button>
                </form>
            </div>

            <!-- Right Side: Vehicle details summary -->
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
                    <h3>Linked Vehicle Details</h3>
                    <span class="badge <?php echo $vehicle['is_approved'] ? 'badge-completed' : 'badge-pending'; ?>">
                        <?php echo $vehicle['is_approved'] ? 'Approved' : 'Pending Verification'; ?>
                    </span>
                </div>

                <div style="background-color:var(--bg-tertiary); padding: 1.2rem; border-radius: 8px; border:1px solid var(--border-color); display:flex; flex-direction:column; gap:12px;">
                    <div>
                        <span class="text-secondary" style="font-size:0.8rem; display:block;">Vehicle Class / Brand</span>
                        <strong><?php echo sanitize($vehicle['make'] . ' ' . $vehicle['model']); ?></strong>
                    </div>
                    <div>
                        <span class="text-secondary" style="font-size:0.8rem; display:block;">Year Manufactured</span>
                        <strong><?php echo sanitize($vehicle['year']); ?></strong>
                    </div>
                    <div>
                        <span class="text-secondary" style="font-size:0.8rem; display:block;">Vehicle Color</span>
                        <strong><?php echo sanitize($vehicle['color']); ?></strong>
                    </div>
                    <div>
                        <span class="text-secondary" style="font-size:0.8rem; display:block;">Plate registration Number</span>
                        <strong style="color:var(--accent-cyan); font-size:1.1rem;"><?php echo sanitize($vehicle['plate_no']); ?></strong>
                    </div>
                    <div>
                        <span class="text-secondary" style="font-size:0.8rem; display:block;">Vehicle Category</span>
                        <strong style="text-transform:uppercase;"><?php echo sanitize($vehicle['vehicle_type']); ?></strong>
                    </div>
                </div>

                <p class="text-secondary" style="font-size: 0.8rem; margin-top: 1.5rem;"><i class="fa-solid fa-circle-info"></i> To link a new vehicle or modify plate registration records, submit details directly to active administrators.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
   


































