<?php
// ============================================================
// RideEase – Database Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'rideease_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// App constants
define('APP_NAME', 'RideEase');

// 100% immune relative path directory level detection
$basePath = str_replace('\\', '/', dirname(__DIR__));
$scriptFilename = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
$relativeScript = preg_replace('#^' . preg_quote($basePath, '#') . '#i', '', $scriptFilename);
$relativeScript = trim($relativeScript, '/');
$levels = substr_count($relativeScript, '/');
$webRoot = str_repeat('../', $levels);
$webRoot = rtrim($webRoot, '/');
if ($webRoot === '') {
    $webRoot = '.';
}

define('BASE_URL', $webRoot);
define('BASE_PATH', $basePath);

// Fare constants
define('BASE_FARE', 50.00);          // BDT
define('RATE_PER_KM', 12.00);        // BDT per km
define('PLATFORM_COMMISSION', 20.00); // Percentage

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed. Please check XAMPP is running.'
            ]));
        }
    }
    return $pdo;
}
