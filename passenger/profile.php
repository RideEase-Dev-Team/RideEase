<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

requirePassenger();

$db = getDB();
$userId = currentUserId();

if (isset($_POST['update_profile'])) {
    verifyCsrf();
    
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);

    if (empty($name) || empty($phone)) {
        setFlash('danger', "Please fill in all profile fields.");
    } else {
        try {
            $stmt = $db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $userId]);
            
            $_SESSION['name'] = $name;
            setFlash('success', "Profile details successfully updated.");
            redirect('/passenger/profile.php');
        } catch (PDOException $e) {
            setFlash('danger', "Failed to update profile details.");
        }
    }
}

if (isset($_POST['add_favorite'])) {
    verifyCsrf();
    
    $label = sanitize($_POST['label']);
    $address = sanitize($_POST['address']);

    if (empty($label) || empty($address)) {
        setFlash('danger', "Please specify label and address details.");
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO favorite_locations (user_id, label, address) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $label, $address]);
            
            setFlash('success', "Added favorite location: " . $label);
            redirect('/passenger/profile.php');
        } catch (PDOException $e) {
            setFlash('danger', "Failed to store favorite location.");
        }
    }
}

if (isset($_GET['del_fav'])) {
    $favId = intval($_GET['del_fav']);
    
    try {
        $stmt = $db->prepare("DELETE FROM favorite_locations WHERE id = ? AND user_id = ?");
        $stmt->execute([$favId, $userId]);
        
        setFlash('success', "Favorite location deleted.");
        redirect('/passenger/profile.php');
    } catch (PDOException $e) {
        setFlash('danger', "Failed to remove location.");
    }
}

try {
    $userStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    
    $favStmt = $db->prepare("SELECT * FROM favorite_locations WHERE user_id = ?");
    $favStmt->execute([$userId]);
    $favorites = $favStmt->fetchAll();
} catch (PDOException $e) {
    setFlash('danger', "Failed to load database details.");
}

$pageTitle = "My Profile Settings";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
            <li><a href="book_ride.php"><i class="fa-solid fa-map-location-dot"></i> Book Ride</a></li>
            <li><a href="ride_history.php"><i class="fa-solid fa-list-ul"></i> Ride History</a></li>
            <li><a href="profile.php" class="active"><i class="fa-solid fa-user-gear"></i> Account Settings</a></li>
        </ul>
    </aside>

    <div class="dashboard-content">
        <h1 class="gradient-text">Account Settings</h1>
        <p class="text-secondary" style="margin-bottom: 2rem;">Manage details and save locations for quick checkouts</p>

        <div class="grid-2">
        
            <div class="card">
                <h3>Personal Profile Details</h3>
                <form action="profile.php" method="POST" style="margin-top: 1rem;">
                    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="email">Email Address (Read Only)</label>
                        <input type="email" id="email" class="form-control" value="<?php echo sanitize($user['email']); ?>" disabled style="background-color: var(--bg-tertiary);">
                    </div>

                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" required value="<?php echo sanitize($user['name']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="form-control" required value="<?php echo sanitize($user['phone']); ?>">
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary" style="width:100%; margin-top:1rem;">Save Changes</button>
                </form>
            </div>

            <div>
                <div class="card" style="margin-bottom: 1.5rem;">
                    <h3>Add Favorite Location</h3>
                    <form action="profile.php" method="POST" style="margin-top:1rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                        
                        <div class="form-group">
                            <label for="label">Location Name (Label)</label>
                            <input type="text" name="label" id="label" class="form-control" placeholder="E.g., Office, Grandma's, Gym" required>
                        </div>

                        <div class="form-group">
                            <label for="address">Full Address / Landmark</label>
                            <input type="text" name="address" id="address" class="form-control" placeholder="E.g., Dhanmondi Lake, Dhaka" required>
                        </div>

                        <button type="submit" name="add_favorite" class="btn btn-secondary" style="width:100%;"><i class="fa-solid fa-plus"></i> Save Location</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Saved Locations</h3>
                    <div style="margin-top:1rem;">
                        <?php if (empty($favorites)): ?>
                            <p class="text-muted" style="font-size:0.9rem;">No favorite locations saved yet.</p>
                        <?php else: ?>
                            <ul style="list-style:none; padding:0; display:flex; flex-direction:column; gap:10px;">
                                <?php foreach ($favorites as $fav): ?>
                                    <li style="display:flex; justify-content:space-between; align-items:center; background-color: var(--bg-tertiary); padding: 0.8rem; border-radius: 8px; border:1px solid var(--border-color);">
                                        <div>
                                            <strong style="color:var(--accent-cyan);"><?php echo sanitize($fav['label']); ?></strong>
                                            <div style="font-size:0.8rem;" class="text-secondary"><?php echo sanitize($fav['address']); ?></div>
                                        </div>
                                        <a href="profile.php?del_fav=<?php echo $fav['id']; ?>" class="text-danger" style="text-decoration:none;" onclick="return confirm('Remove favorite location?');">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
