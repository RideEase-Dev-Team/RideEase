<?php
$pageTitle = "Welcome";
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero">
    <div class="hero-content">
        <h1 class="gradient-text">Smart Ride Sharing Made Effortless</h1>
        <p>Book rides instantly, track driver routes in real-time, compute fair estimations with no hidden surcharges, and travel safely with active SOS modules.</p>
        
        <div style="display: flex; gap: 15px; margin-top: 2rem;">
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-primary">Go to Dashboard <i class="fa-solid fa-arrow-right"></i></a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/auth/register.php" class="btn btn-primary">Get Started <i class="fa-solid fa-user-plus"></i></a>
                <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-secondary">Login Account <i class="fa-solid fa-right-to-bracket"></i></a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="hero-image">
        <div class="card" style="width: 100%; max-width: 450px; border-color: var(--accent-cyan);">
            <h3 class="gradient-text"><i class="fa-solid fa-clock-rotate-left"></i> Peak Hour Multipliers</h3>
            <p class="text-secondary" style="font-size: 0.9rem; margin-bottom: 1rem;">Dynamic algorithms calculate rates according to time slots to match customer demands and availability.</p>
            <div style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <div style="display:flex; justify-content:space-between; margin-bottom: 5px;">
                    <span class="text-secondary">Base Fee:</span>
                    <strong style="color: var(--accent-cyan);">৳ 50.00</strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom: 5px;">
                    <span class="text-secondary">Per KM Price:</span>
                    <strong>৳ 12.00</strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span class="text-secondary">SOS Status:</span>
                    <span class="badge badge-completed">Online & Protected</span>
                </div>
            </div>
        </div>
    </div>
</div>

<section style="padding: 4rem 0;">
    <h2 class="text-center gradient-text" style="font-size: 2.2rem; margin-bottom: 3rem;">Core Platform Features</h2>
    
    <div class="grid-3">
        <div class="card">
            <div style="font-size: 2rem; color: var(--accent-cyan); margin-bottom: 1rem;">
                <i class="fa-solid fa-map-location-dot"></i>
            </div>
            <h3>Smart Ride Booking</h3>
            <p class="text-secondary">Input pickup & dropoff coordinates using Leaflet map coordinates. Calculates shortest path distances instantly.</p>
        </div>
        
        <div class="card">
            <div style="font-size: 2rem; color: var(--accent-purple); margin-bottom: 1rem;">
                <i class="fa-solid fa-credit-card"></i>
            </div>
            <h3>Simulated Cash & Cards</h3>
            <p class="text-secondary">Simulate wallet payments using bKash, standard Cash, or Visa card checkouts, updating driver payout earnings directly.</p>
        </div>
        
        <div class="card">
            <div style="font-size: 2rem; color: var(--danger); margin-bottom: 1rem;">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h3>SOS Safety Dashboards</h3>
            <p class="text-secondary">Trigger instant distress alerts if you experience emergency events. Instantly informs active admin dashboards.</p>
        </div>
    </div>
</section>



<?php require_once __DIR__ . '/includes/footer.php'; ?>
