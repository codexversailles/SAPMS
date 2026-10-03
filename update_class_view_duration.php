<?php
/**
 * Update Class View Duration
 * 
 * This script updates the duration field of a class view log entry
 * when a student leaves/closes the class page.
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
if (!isset($_POST['log_id']) || !isset($_POST['duration'])) {
    echo json_encode(['success' => false, 'message' => 'Log ID and duration are required']);
    exit;
}

$log_id = $_POST['log_id'];
$duration = intval($_POST['duration']); // Duration in seconds
$student_id = $_SESSION['student_id'];

// Validate duration (prevent unreasonable values)
if ($duration <= 0 || $duration > 86400) { // Max 24 hours (86400 seconds)
    echo json_encode(['success' => false, 'message' => 'Invalid duration value']);
    exit;
}

try {
    // Update the view duration (ensure the log belongs to the current student)
    $stmt = $pdo->prepare("
        UPDATE class_view_logs
        SET view_duration = ?
        WHERE id = ? AND student_id = ?
    ");
    $stmt->execute([$duration, $log_id, $student_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'View duration updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'No matching log entry found or not authorized'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error updating view duration: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating the view duration']);
}
?> 