<?php
// ============================================================
// RideEase – Admin Pricing Management & Peak Hours
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requireAdmin();

$db = getDB();

// Handle Peak Hour Entry Creation
if (isset($_POST['create_peak'])) {
    verifyCsrf();
    
    $day = intval($_POST['day_of_week']);
    $start = sanitize($_POST['start_time']);
    $end = sanitize($_POST['end_time']);
    $mult = floatval($_POST['multiplier']);
    $label = sanitize($_POST['label'] ?? 'Peak hour rule');

    if ($mult < 1.0) {
        setFlash('danger', "Peak multiplier must be at least 1.00.");
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO peak_hours (day_of_week, start_time, end_time, multiplier, label, is_active) 
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$day, $start, $end, $mult, $label]);
            setFlash('success', "Peak pricing rule successfully added.");
        } catch (PDOException $e) {
            setFlash('danger', "Failed to create pricing rule.");
        }
    }
    redirect('/admin/pricing.php');
}

// Handle Toggle active
if (isset($_GET['toggle'])) {
    $pId = intval($_GET['toggle']);
    $state = intval($_GET['state']);
    
    try {
        $stmt = $db->prepare("UPDATE peak_hours SET is_active = ? WHERE id = ?");
        $stmt->execute([$state, $pId]);
        setFlash('success', "Pricing rule state updated.");
    } catch (PDOException $e) {
        setFlash('danger', "Failed to toggle pricing rule.");
    }
    redirect('/admin/pricing.php');
}

// Handle Delete peak rule
if (isset($_GET['delete'])) {
    $pId = intval($_GET['delete']);
    try {
        $stmt = $db->prepare("DELETE FROM peak_hours WHERE id = ?");
        $stmt->execute([$pId]);
        setFlash('success', "Pricing rule successfully removed.");
    } catch (PDOException $e) {
        setFlash('danger', "Failed to remove pricing rule.");
    }
    redirect('/admin/pricing.php');
}

// Fetch Peak hour configurations
$daysMap = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
try {
    $stmt = $db->query("SELECT * FROM peak_hours ORDER BY day_of_week, start_time");
    $peaks = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error compiling pricing records.");
}

$pageTitle = "Pricing Management";
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
            <li><a href="coupons.php"><i class="fa-solid fa-ticket"></i> Promo Coupons</a></li>
            <li><a href="pricing.php" class="active"><i class="fa-solid fa-circle-dollar-to-slot"></i> Peak Surcharges</a></li>
            <li><a href="sos_alerts.php"><i class="fa-solid fa-triangle-exclamation"></i> SOS Dispatches</a></li>
            <li><a href="complaints.php"><i class="fa-solid fa-headset"></i> Complaint Tickets</a></li>
            <li><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Sales Reports</a></li>
        </ul>
    </aside>

    <div class="dashboard-content">
        <h1 class="gradient-text">Pricing & Peak Hours Surcharges</h1>
        <p class="text-secondary" style="margin-bottom: 2rem;">Configure base fares and dynamic peak pricing multipliers</p>

        <div class="grid-2">
            <!-- Left Panel: Create Pricing Rule Form -->
            <div class="card">
                <h3>Add Peak Hour Multiplier</h3>
                <form action="pricing.php" method="POST" style="margin-top:1rem;">
                    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="label">Rule Label / Description</label>
                        <input type="text" name="label" id="label" class="form-control" placeholder="E.g., Morning Rush Hour" required>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="day_of_week">Day of Week</label>
                            <select name="day_of_week" id="day_of_week" class="form-control">
                                <option value="1">Monday</option>
                                <option value="2">Tuesday</option>
                                <option value="3">Wednesday</option>
                                <option value="4">Thursday</option>
                                <option value="5">Friday</option>
                                <option value="6">Saturday</option>
                                <option value="0">Sunday</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="multiplier">Multiplier Value</label>
                            <input type="number" name="multiplier" id="multiplier" class="form-control" min="1.00" max="4.00" step="0.05" value="1.50" required>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="start_time">Start Time</label>
                            <input type="time" name="start_time" id="start_time" class="form-control" required value="08:00">
                        </div>
                        <div class="form-group">
                            <label for="end_time">End Time</label>
                            <input type="time" name="end_time" id="end_time" class="form-control" required value="10:00">
                        </div>
                    </div>

                    <button type="submit" name="create_peak" class="btn btn-primary" style="width:100%; margin-top:1rem;">Add Pricing Rule</button>
                </form>
            </div>

            <!-- Right Panel: Rules list -->
            <div class="card">
                <h3>Dynamic Surcharge Registry</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time Frame</th>
                                <th>Multiplier</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($peaks)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No peak pricing rules saved. Default (x1.00) applies.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($peaks as $p): ?>
                                    <tr>
                                        <td><strong><?php echo $daysMap[$p['day_of_week']]; ?></strong></td>
                                        <td>
                                            <div style="font-size:0.85rem;" class="text-secondary">
                                                <?php echo date('h:i A', strtotime($p['start_time'])); ?> - <?php echo date('h:i A', strtotime($p['end_time'])); ?>
                                            </div>
                                            <div style="font-size:0.75rem; color:#fff;"><?php echo sanitize($p['label']); ?></div>
                                        </td>
                                        <td><strong style="color:var(--accent-cyan);">x<?php echo $p['multiplier']; ?></strong></td>
                                        <td>
                                            <span class="badge <?php echo $p['is_active'] ? 'badge-completed' : 'badge-cancelled'; ?>">
                                                <?php echo $p['is_active'] ? 'Active' : 'Disabled'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display:flex; gap:5px;">
                                                <?php if ($p['is_active']): ?>
                                                    <a href="pricing.php?toggle=<?php echo $p['id']; ?>&state=0" class="btn btn-secondary" style="padding:0.3rem 0.5rem; font-size:0.75rem;">Disable</a>
                                                <?php else: ?>
                                                    <a href="pricing.php?toggle=<?php echo $p['id']; ?>&state=1" class="btn btn-success" style="padding:0.3rem 0.5rem; font-size:0.75rem;">Enable</a>
                                                <?php endif; ?>
                                                <a href="pricing.php?delete=<?php echo $p['id']; ?>" class="btn btn-danger" style="padding:0.3rem 0.5rem; font-size:0.75rem;" onclick="return confirm('Remove rule?');"><i class="fa-solid fa-trash"></i></a>
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
