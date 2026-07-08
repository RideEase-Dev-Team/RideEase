<?php
// ============================================================
// RideEase – Admin User Management
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireAdmin();

$db = getDB();

$search = sanitize($_GET['q'] ?? '');
$role = sanitize($_GET['role'] ?? 'all');

// Handle Deactivate / Reactivate
if (isset($_GET['toggle_active'])) {
    $uId = intval($_GET['toggle_active']);
    $state = intval($_GET['state']);
    
    // Prevent self-deactivation
    if ($uId === currentUserId()) {
        setFlash('danger', "Self deactivation is prevented.");
    } else {
        try {
            $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$state, $uId]);
            setFlash('success', "User activation state successfully toggled.");
        } catch (PDOException $e) {
            setFlash('danger', "Action failed due to database constraints.");
        }














    }














    redirect('/admin/users.php');
}















// Handle Delete User
if (isset($_GET['delete_user'])) {
    $uId = intval($_GET['delete_user']);
    
    if ($uId === currentUserId()) {
        setFlash('danger', "Self deletion is prevented.");
    } else {
        try {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$uId]);
            setFlash('success', "User account deleted successfully.");
        } catch (PDOException $e) {
            setFlash('danger', "Failed to delete user. They might have active dependencies.");
        }














    }














    redirect('/admin/users.php');
}















// Compile Users List Query
try {
    $sql = "SELECT id, name, email, phone, role, is_active, created_at FROM users WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $searchParam = "%$search%";
        array_push($params, $searchParam, $searchParam, $searchParam);
    }














    
    if ($role !== 'all') {
        $sql .= " AND role = ?";
        array_push($params, $role);
    }














    
    $sql .= " ORDER BY id DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error compiling user list: " . $e->getMessage());
}















$pageTitle = "User Management";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Admin Dashboard</a></li>
            <li><a href="users.php" class="active"><i class="fa-solid fa-users"></i> User Accounts</a></li>
            <li><a href="drivers.php"><i class="fa-solid fa-id-card"></i> Driver Partners</a></li>
            <li><a href="vehicles.php"><i class="fa-solid fa-truck-pickup"></i> Cab Approvals</a></li>
            <li><a href="rides.php"><i class="fa-solid fa-map-location"></i> Ride Monitors</a></li>
            <li><a href="coupons.php"><i class="fa-solid fa-ticket"></i> Promo Coupons</a></li>
            <li><a href="pricing.php"><i class="fa-solid fa-circle-dollar-to-slot"></i> Peak Surcharges</a></li>
            <li><a href="sos_alerts.php"><i class="fa-solid fa-triangle-exclamation"></i> SOS Dispatches</a></li>
            <li><a href="complaints.php"><i class="fa-solid fa-headset"></i> Complaint Tickets</a></li>
            <li><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Sales Reports</a></li>
        </ul>
    </aside>

    <div class="dashboard-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
            <div>
                <h1 class="gradient-text">User Accounts Manager</h1>
                <p class="text-secondary">Track platform passenger profiles and credentials</p>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="card" style="margin-bottom: 1.5rem; padding: 1.2rem;">
            <form action="users.php" method="GET" style="display:flex; gap:15px; align-items:center; flex-wrap:wrap;">
                <div style="flex:1; min-width:200px;">
                    <input type="text" name="q" class="form-control" placeholder="Search by name, email, or phone..." value="<?php echo sanitize($search); ?>">
                </div>
                <div>
                    <select name="role" class="form-control">
                        <option value="all" <?php echo $role === 'all' ? 'selected' : ''; ?>>All Roles</option>
                        <option value="passenger" <?php echo $role === 'passenger' ? 'selected' : ''; ?>>Passengers</option>
                        <option value="driver" <?php echo $role === 'driver' ? 'selected' : ''; ?>>Drivers</option>
                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Administrators</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter Search</button>
                <a href="users.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <!-- Accounts lists -->
        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email Address</th>
                            <th>Phone Number</th>
                            <th>Role Profile</th>
                            <th>Created Date</th>
                            <th>Activation State</th>
                            <th>Action Controls</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No users found matching requirements.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>#<?php echo $u['id']; ?></td>
                                    <td><strong><?php echo sanitize($u['name']); ?></strong></td>
                                    <td><?php echo sanitize($u['email']); ?></td>
                                    <td><?php echo sanitize($u['phone']); ?></td>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $u['role'] === 'admin' ? 'var(--accent-purple)' : ($u['role'] === 'driver' ? 'var(--accent-cyan)' : 'var(--bg-tertiary)'); ?>; color:#fff;">
                                            <?php echo ucfirst($u['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $u['is_active'] ? 'badge-completed' : 'badge-cancelled'; ?>">
                                            <?php echo $u['is_active'] ? 'Active' : 'Deactivated'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:5px;">
                                            <?php if ($u['is_active']): ?>
                                                <a href="users.php?toggle_active=<?php echo $u['id']; ?>&state=0" class="btn btn-secondary" style="padding:0.3rem 0.6rem; font-size:0.8rem;" onclick="return confirm('Deactivate account?');">Suspend</a>
                                            <?php else: ?>
                                                <a href="users.php?toggle_active=<?php echo $u['id']; ?>&state=1" class="btn btn-success" style="padding:0.3rem 0.6rem; font-size:0.8rem;">Reactivate</a>
                                            <?php endif; ?>
                                            
                                            <?php if ($u['id'] !== currentUserId()): ?>
                                                <a href="users.php?delete_user=<?php echo $u['id']; ?>" class="btn btn-danger" style="padding:0.3rem 0.6rem; font-weight: 500; font-size:0.8rem;" onclick="return confirm('Permanently delete account? This cannot be undone.');"><i class="fa-solid fa-trash-can"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>






































































