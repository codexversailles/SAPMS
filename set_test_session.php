<?php
// Set error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set headers
header('Content-Type: application/json');

// Debug session
$debug = [];
$debug['session_before'] = $_SESSION;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_verified') {
    // Get email from POST or use default
    $email = isset($_POST['email']) ? $_POST['email'] : 'test@example.com';
    
    // Set session variables to bypass email verification
    $_SESSION['email_verified'] = true;
    $_SESSION['verification_email'] = $email;
    
    // Debug updated session
    $debug['session_after'] = $_SESSION;
    $debug['email_set'] = $email;
    
    echo json_encode([
        'success' => true,
        'message' => 'Verification bypass enabled for email: ' . $email,
        'debug' => $debug
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request',
        'debug' => $debug,
        'post' => $_POST
    ]);
}
?> 