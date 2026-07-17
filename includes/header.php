<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/auth_check.php';

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' | ' . APP_NAME : APP_NAME . ' – Smart Ride Sharing'; ?></title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="app-container">
    <header>
        <nav class="navbar">
            <a href="<?php echo BASE_URL; ?>/" class="logo">
                <i class="fa-solid fa-car-side" style="margin-right: 10px; color: var(--accent-cyan);"></i>
                Ride<span>Ease</span>
            </a>
            
            <div class="menu-toggle" id="mobile-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
            
            <ul class="nav-links">
                <li><a href="<?php echo BASE_URL; ?>/">Home</a></li>
                <?php if (isLoggedIn()): ?>
                    <?php if (isPassenger()): ?>
                        <li><a href="<?php echo BASE_URL; ?>/passenger/dashboard.php">Dashboard</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/passenger/book_ride.php">Book Ride</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/passenger/ride_history.php">My Rides</a></li>
                    <?php elseif (isDriver()): ?>
                        <li><a href="<?php echo BASE_URL; ?>/driver/dashboard.php">Driver Hub</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/driver/earnings.php">Earnings</a></li>
                    <?php elseif (isAdmin()): ?>
                        <li><a href="<?php echo BASE_URL; ?>/admin/dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>
                    
                    <li style="border-left: 1px solid var(--border-color); padding-left: 15px; margin-left: 5px;">
                        <span class="text-secondary" style="font-size: 0.9rem; font-weight:600;">
                            <i class="fa-regular fa-circle-user" style="color: var(--accent-cyan);"></i> 
                            <?php echo sanitize(currentUserName()); ?>
                        </span>
                    </li>
                    <li><a href="<?php echo BASE_URL; ?>/auth/logout.php" class="btn btn-secondary" style="padding: 0.4rem 1rem;">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo BASE_URL; ?>/auth/login.php">Login</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/auth/register.php" class="btn btn-primary" style="padding: 0.5rem 1.2rem; color: #000;">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="container">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <i class="fa-solid <?php echo $flash['type'] === 'success' ? 'fa-circle-check' : ($flash['type'] === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark'); ?>"></i>
                <span><?php echo sanitize($flash['message']); ?></span>
            </div>
        <?php endif; ?>
