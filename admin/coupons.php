<?php
// ============================================================
// RideEase – Admin Coupon CRUD
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireAdmin();

$db = getDB();

// Handle Coupon Creation
if (isset($_POST['create_coupon'])) {
    verifyCsrf();
    
    $code = strtoupper(sanitize($_POST['code']));
    $type = sanitize($_POST['discount_type']);
    $value = floatval($_POST['discount_value']);
    $min = floatval($_POST['min_fare']);
    $max = intval($_POST['max_uses']);
    $expiry = sanitize($_POST['expires_at']);

    if (empty($code) || $value <= 0 || empty($expiry)) {
        setFlash('danger', "All fields are required. Discount value must be positive.");
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO coupons (code, discount_type, discount_value, min_fare, max_uses, expires_at, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$code, $type, $value, $min, $max, $expiry]);
            setFlash('success', "Coupon code $code successfully created.");
        } catch (PDOException $e) {
            setFlash('danger', "Failed to create coupon code. Code might be duplicate.");
        }
    }
    redirect('/admin/coupons.php');
}

// Handle Toggle Active
if (isset($_GET['toggle'])) {
    $cId = intval($_GET['toggle']);
    $state = intval($_GET['state']);
    
    try {
        $stmt = $db->prepare("UPDATE coupons SET is_active = ? WHERE id = ?");
        $stmt->execute([$state, $cId]);
        setFlash('success', "Coupon active state updated.");
    } catch (PDOException $e) {
        setFlash('danger', "Failed to toggle coupon state.");
    }
    redirect('/admin/coupons.php');
}

// Handle Delete Coupon
if (isset($_GET['delete'])) {
    $cId = intval($_GET['delete']);
    
    try {
        $stmt = $db->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$cId]);
        setFlash('success', "Coupon code permanently deleted.");
    } catch (PDOException $e) {
        setFlash('danger', "Failed to delete coupon.");
    }
    redirect('/admin/coupons.php');
}

// Fetch Coupons list
try {
    $stmt = $db->query("SELECT * FROM coupons ORDER BY id DESC");
    $coupons = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching coupons.");
}

$pageTitle = "Promo Coupons";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Admin Dashboard</a></li>
            <li><a href="users.php"><i class="fa-solid fa-users"></i> User Accounts</a></li>
            <li><a href="drivers.php"><i class="fa-solid fa-id-card"></i> Driver Partners</a></li>
            <li><a href="vehicles.php"><i class="fa-solid fa-truck-pickup"></i> Cab Approvals</a></li>
            <li><a href="rides.php"><i class="fa-solid fa-map-location"></i> Ride Monitors</a></li>
            <li><a href="coupons.php" class="active"><i class="fa-solid fa-ticket"></i> Promo Coupons</a></li>
            <li><a href="pricing.php"><i class="fa-solid fa-circle-dollar-to-slot"></i> Peak Surcharges</a></li>
            <li><a href="sos_alerts.php"><i class="fa-solid fa-triangle-exclamation"></i> SOS Dispatches</a></li>
            <li><a href="complaints.php"><i class="fa-solid fa-headset"></i> Complaint Tickets</a></li>
            <li><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Sales Reports</a></li>
        </ul>
    </aside>

    <div class="dashboard-content">
        <h1 class="gradient-text">Promo Coupons Manager</h1>
        <p class="text-secondary" style="margin-bottom: 2rem;">Manage promotional discount coupons for riders</p>

        <div class="grid-2">
            <!-- Left Side: Coupon Creation Form -->
            <div class="card">
                <h3>Create New Coupon</h3>
                <form action="coupons.php" method="POST" style="margin-top: 1rem;">
                    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="code">Coupon Code</label>
                        <input type="text" name="code" id="code" class="form-control" placeholder="E.g., SAVE30" required style="text-transform:uppercase;">
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="discount_type">Discount Type</label>
                            <select name="discount_type" id="discount_type" class="form-control">
                                <option value="percent">Percent (%)</option>
                                <option value="fixed">Fixed BDT (৳)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="discount_value">Discount Value</label>
                            <input type="number" name="discount_value" id="discount_value" class="form-control" min="1" step="0.01" placeholder="30" required>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="min_fare">Min Ride Fare Required (৳)</label>
                            <input type="number" name="min_fare" id="min_fare" class="form-control" min="0" step="1" value="0">
                        </div>
                        <div class="form-group">
                            <label for="max_uses">Maximum Allowed Uses</label>
                            <input type="number" name="max_uses" id="max_uses" class="form-control" min="1" value="100" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="expires_at">Expiration Date</label>
                        <input type="date" name="expires_at" id="expires_at" class="form-control" required value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                    </div>

                    <button type="submit" name="create_coupon" class="btn btn-primary" style="width:100%; margin-top:1rem;">Generate Coupon</button>
                </form>
            </div>

            <!-- Right Side: Coupons List -->
            <div class="card">
                <h3>Active Coupons Registry</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Discount</th>
                                <th>Expiry</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coupons)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No coupon codes registered yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($coupons as $c): ?>
                                    <tr>
                                        <td><strong style="color:var(--accent-cyan);"><?php echo sanitize($c['code']); ?></strong></td>
                                        <td>
                                            <?php echo $c['discount_type'] === 'percent' ? $c['discount_value'] . '%' : formatBDT($c['discount_value']); ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($c['expires_at'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $c['is_active'] ? 'badge-completed' : 'badge-cancelled'; ?>">
                                                <?php echo $c['is_active'] ? 'Active' : 'Disabled'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display:flex; gap:5px;">
                                                <?php if ($c['is_active']): ?>
                                                    <a href="coupons.php?toggle=<?php echo $c['id']; ?>&state=0" class="btn btn-secondary" style="padding:0.3rem 0.5rem; font-size:0.75rem;">Disable</a>
                                                <?php else: ?>
                                                    <a href="coupons.php?toggle=<?php echo $c['id']; ?>&state=1" class="btn btn-success" style="padding:0.3rem 0.5rem; font-size:0.75rem;">Enable</a>
                                                <?php endif; ?>
                                                <a href="coupons.php?delete=<?php echo $c['id']; ?>" class="btn btn-danger" style="padding:0.3rem 0.5rem; font-size:0.75rem;" onclick="return confirm('Delete coupon permanently?');"><i class="fa-solid fa-trash"></i></a>
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
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
