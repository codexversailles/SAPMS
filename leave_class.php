<?php
// Start session to get student ID
session_start();

// Include database connection
require_once 'db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to leave a class'
    ]);
    exit;
}

// Get data from POST request
$data = json_decode(file_get_contents('php://input'), true);

// Check if class ID is provided
if (!isset($data['class_id']) || empty($data['class_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Class ID is required'
    ]);
    exit;
}

// Get student ID and class ID
$student_id = intval($_SESSION['student_id']);
$class_id = intval($data['class_id']);

try {
    // First, check if the student is actually enrolled in this class
    $check_query = "SELECT * FROM class_students WHERE student_id = ? AND class_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $student_id, $class_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'You are not enrolled in this class'
        ]);
        exit;
    }
    
    // Begin a transaction to ensure data integrity
    $conn->begin_transaction();
    
    // First, delete any submissions the student has made for assessments in this class
    $submissions_query = "DELETE ss FROM student_submissions ss 
                         JOIN assessments a ON ss.assessment_id = a.id 
                         WHERE ss.student_id = ? AND a.class_id = ?";
    $submissions_stmt = $conn->prepare($submissions_query);
    $submissions_stmt->bind_param("ii", $student_id, $class_id);
    $submissions_stmt->execute();
    
    // Now, remove the student from the class
    $delete_query = "DELETE FROM class_students WHERE student_id = ? AND class_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $student_id, $class_id);
    $delete_stmt->execute();
    
    // Commit the transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'You have successfully left the class'
    ]);
} catch (Exception $e) {
    // If there's an error, roll back the transaction
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error leaving class: ' . $e->getMessage()
    ]);
}
?> 