<?php
// ============================================================
// RideEase – Admin Driver Management
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireAdmin();

$db = getDB();

// Handle Approve Partner
if (isset($_GET['approve'])) {
    $dId = intval($_GET['approve']);
    try {
        $stmt = $db->prepare("UPDATE drivers SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$dId]);
        setFlash('success', "Driver application verified and approved.");
    } catch (PDOException $e) {
        setFlash('danger', "Failed to approve driver.");
    }














    redirect('/admin/drivers.php');
}















// Handle Suspend Toggle Partner
if (isset($_GET['toggle_suspend'])) {
    $dId = intval($_GET['toggle_suspend']);
    $state = intval($_GET['state']);
    
    try {
        $stmt = $db->prepare("UPDATE drivers SET is_suspended = ? WHERE id = ?");
        $stmt->execute([$state, $dId]);
        setFlash('success', $state ? "Driver has been suspended from taking rides." : "Driver suspension lifted.");
    } catch (PDOException $e) {
        setFlash('danger', "Failed to toggle driver suspension status.");
    }














    redirect('/admin/drivers.php');
}















// Fetch drivers listing
try {
    $stmt = $db->query("
        SELECT d.*, u.name, u.email, u.phone, u.is_active 
        FROM drivers d
        JOIN users u ON d.user_id = u.id
        ORDER BY d.id DESC
    ");
    $drivers = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error compiling drivers list.");
}















$pageTitle = "Driver Management";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Admin Dashboard</a></li>
            <li><a href="users.php"><i class="fa-solid fa-users"></i> User Accounts</a></li>
            <li><a href="drivers.php" class="active"><i class="fa-solid fa-id-card"></i> Driver Partners</a></li>
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
                <h1 class="gradient-text">Driver Partners Manager</h1>
                <p class="text-secondary">Track platform cab driver verification states and profiles</p>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Driver Name</th>
                            <th>Email & Phone</th>
                            <th>License & NID</th>
                            <th>Rating</th>
                            <th>Trips</th>
                            <th>Verification State</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($drivers)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No driver application entries logged.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($drivers as $d): ?>
                                <tr>
                                    <td>#<?php echo $d['id']; ?></td>
                                    <td><strong><?php echo sanitize($d['name']); ?></strong></td>
                                    <td>
                                        <span style="font-size:0.85rem; display:block;"><?php echo sanitize($d['email']); ?></span>
                                        <span style="font-size:0.85rem;" class="text-secondary"><?php echo sanitize($d['phone']); ?></span>
                                    </td>
                                    <td>
                                        <span style="font-size:0.85rem; display:block;"><strong>License:</strong> <?php echo sanitize($d['license_no']); ?></span>
                                        <span style="font-size:0.85rem;" class="text-secondary"><strong>NID:</strong> <?php echo sanitize($d['nid_no']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($d['avg_rating'], 2); ?> <i class="fa-solid fa-star" style="color:var(--warning);"></i></strong>
                                    </td>
                                    <td><?php echo $d['total_trips']; ?> trips</td>
                                    <td>
                                        <?php if ($d['is_suspended']): ?>
                                            <span class="badge badge-cancelled">Suspended</span>
                                        <?php elseif (!$d['is_approved']): ?>
                                            <span class="badge badge-pending">Pending Approval</span>
                                        <?php else: ?>
                                            <span class="badge badge-completed">Verified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:5px;">
                                            <?php if (!$d['is_approved']): ?>
                                                <a href="drivers.php?approve=<?php echo $d['id']; ?>" class="btn btn-success" style="padding:0.3rem 0.6rem; font-size:0.8rem;"><i class="fa-solid fa-circle-check"></i> Verify</a>
                                            <?php endif; ?>
                                            
                                            <?php if ($d['is_suspended']): ?>
                                                <a href="drivers.php?toggle_suspend=<?php echo $d['id']; ?>&state=0" class="btn btn-secondary" style="padding:0.3rem 0.6rem; font-size:0.8rem;">Unsuspend</a>
                                            <?php else: ?>
                                                <a href="drivers.php?toggle_suspend=<?php echo $d['id']; ?>&state=1" class="btn btn-danger" style="padding:0.3rem 0.6rem; font-size:0.8rem;" onclick="return confirm('Suspend driver from taking bookings?');">Suspend</a>
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






































































