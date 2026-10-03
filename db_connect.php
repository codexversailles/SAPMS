<?php
// Disable warnings and error output for clean PDF generation
error_reporting(0);
ini_set('display_errors', 0);

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "studyhub";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Don't output anything directly, store in variable
    $db_error = "Connection failed: " . $e->getMessage();
    
    // Only show error in non-PDF/CSV contexts
    if (!isset($_GET['export'])) {
        die("Database connection failed: " . $e->getMessage());
    }
}
// No closing PHP tag to prevent accidental whitespace 