<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "studyhub";

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Application configuration
define('SITE_NAME', 'SAPMS');
define('SITE_URL', 'http://localhost/StudyHub1');

// Session configuration
define('SESSION_LIFETIME', 86400); // 24 hours in seconds
define('COOKIE_LIFETIME', 604800); // 7 days in seconds

// Security configuration
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes in seconds
define('PASSWORD_MIN_LENGTH', 8);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_token() {
    return bin2hex(random_bytes(32));
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function redirect($path) {
    header("Location: " . SITE_URL . $path);
    exit();
}
?> 