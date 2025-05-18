<?php
// Database configuration for XAMPP
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'onsenhub');

// Site configuration
define('SITE_NAME', 'OnsenHub');
define('SITE_URL', 'http://localhost/onsenhub');

// Start session
session_start();

// Error reporting (for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create database connection with error handling
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    // Set error mode - using string version for compatibility
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Include functions
require_once 'auth.php';

// Log activity
function logActivity($action, $details = '') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR']
    ]);
}

function formatCurrency($amount) {
    return '₱' . number_format((float)$amount, 2);
}

// New helper function
function canModifyBookingByTime(string $checkInDateStr): bool {
    try {
        $checkInDateTime = new DateTime($checkInDateStr);
        $now = new DateTime();

        // If check-in date is in the past or is today but time has passed/is too close
        if ($checkInDateTime <= $now) {
            return false;
        }
        
        $interval = $now->diff($checkInDateTime);
        // Calculate total hours remaining
        $hoursRemaining = $interval->days * 24 + $interval->h;
        
        return $hoursRemaining >= 24;
    } catch (Exception $e) {
        error_log("Error in canModifyBookingByTime for date '$checkInDateStr': " . $e->getMessage());
        return false; // Default to not allowing modification on error
    }
}

function formatLargeNumber($number): string {
    if (!is_numeric($number)) {
        // Return non-numeric input as is, or handle as an error
        return (string)$number;
    }
    $original_number = (float)$number;
    $abs_number = abs($original_number);

    $limit_T_from_B = 999.995 * pow(10, 9);  // Approx 1 Trillion (when B would become 1000.00B)
    $limit_B_from_M = 999.995 * pow(10, 6);  // Approx 1 Billion (when M would become 1000.00M)
    $limit_M_from_K = 999.995 * pow(10, 3);  // Approx 1 Million (when K would become 1000.00K)
    $limit_K        = pow(10, 3);            // Minimum for K abbreviation (1 Thousand)

    if ($abs_number >= $limit_T_from_B) {
        return number_format($original_number / pow(10, 12), 2, '.', '') . 'T';
    } elseif ($abs_number >= $limit_B_from_M) {
        return number_format($original_number / pow(10, 9), 2, '.', '') . 'B';
    } elseif ($abs_number >= $limit_M_from_K) {
        return number_format($original_number / pow(10, 6), 2, '.', '') . 'M';
    } elseif ($abs_number >= $limit_K) {
        return number_format($original_number / pow(10, 3), 2, '.', '') . 'K';
    }
    return number_format($original_number, 2, '.', ''); // For numbers less than 1000
}
?>