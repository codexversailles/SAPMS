<?php
/**
 * Class View Logger
 * 
 * This script handles logging when students view a class.
 * It records student ID, class ID, timestamp, and other related information.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db_connect.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in as a student
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in as student']);
    exit;
}

// Check if required data is provided
if (!isset($_POST['class_id'])) {
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit;
}

$student_id = $_SESSION['student_id'];
$class_id = $_POST['class_id'];
$session_id = session_id();
$ip_address = $_SERVER['REMOTE_ADDR'];

// Get device info (simplified)
$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
$device_info = substr($user_agent, 0, 254); // Limit to 255 chars

try {
    // First check if student is enrolled in this class
    $stmt = $pdo->prepare("
        SELECT id FROM class_students
        WHERE student_id = ? AND class_id = ? AND status = 'active'
    ");
    $stmt->execute([$student_id, $class_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Student not enrolled in this class']);
        exit;
    }
    
    // Insert the view log
    $stmt = $pdo->prepare("
        INSERT INTO class_view_logs
        (student_id, class_id, session_id, ip_address, device_info)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $student_id,
        $class_id,
        $session_id,
        $ip_address,
        $device_info
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'View logged successfully',
        'log_id' => $pdo->lastInsertId()
    ]);
    
} catch (PDOException $e) {
    error_log("Error logging class view: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while logging the view']);
}
?> 