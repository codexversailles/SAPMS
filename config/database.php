<?php
// Prevent any output before our JSON response
ob_start();

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'studyhub';

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    // Clear any output buffers
    ob_end_clean();
    
    // Return JSON error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit;
}

// Set character set
$conn->set_charset("utf8mb4");

// Function to safely close the database connection
function closeDbConnection() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
}

// Register shutdown function to ensure connection is closed
register_shutdown_function('closeDbConnection');
?> 