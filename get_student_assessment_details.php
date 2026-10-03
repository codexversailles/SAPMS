<?php
session_start();
require_once 'db_connect.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in as a student - temporarily commented for testing
/*
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in as student']);
    exit;
}
*/

// For testing purposes
if (!isset($_SESSION['student_id'])) {
    $_SESSION['student_id'] = 1; // Use a valid student ID from your database
}

// Get assessment ID from query parameter
$assessment_id = $_GET['id'] ?? null;

if (!$assessment_id) {
    echo json_encode(['success' => false, 'message' => 'Assessment ID is required']);
    exit;
}

try {
    // Check if connection exists
    if (!isset($conn)) {
        throw new Exception('Database connection not available');
    }
    
    // Get assessment details
    $query = "SELECT a.*, c.class_name 
              FROM assessments a
              JOIN classes c ON a.class_id = c.id
              WHERE a.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $assessment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $assessment = $result->fetch_assoc();
    
    if (!$assessment) {
        echo json_encode(['success' => false, 'message' => 'Assessment not found']);
        exit;
    }
    
    // Get associated files
    $file_query = "SELECT * FROM assessment_files WHERE assessment_id = ?";
    $file_stmt = $conn->prepare($file_query);
    $file_stmt->bind_param("i", $assessment_id);
    $file_stmt->execute();
    $file_result = $file_stmt->get_result();
    
    $assessment['files'] = [];
    while ($file = $file_result->fetch_assoc()) {
        $assessment['files'][] = $file;
    }
    
    // Get student's current submission (if any)
    $submission_query = "SELECT * FROM student_submissions WHERE student_id = ? AND assessment_id = ?";
    $submission_stmt = $conn->prepare($submission_query);
    $submission_stmt->bind_param("ii", $_SESSION['student_id'], $assessment_id);
    $submission_stmt->execute();
    $submission_result = $submission_stmt->get_result();
    $submission = $submission_result->fetch_assoc();
    
    if ($submission) {
        $assessment['submission'] = $submission;
        
        // Get submission files if available
        $submission_files_query = "SELECT * FROM submission_files WHERE submission_id = ?";
        $submission_files_stmt = $conn->prepare($submission_files_query);
        $submission_files_stmt->bind_param("i", $submission['id']);
        $submission_files_stmt->execute();
        $submission_files_result = $submission_files_stmt->get_result();
        
        $assessment['submission']['files'] = [];
        while ($submission_file = $submission_files_result->fetch_assoc()) {
            $assessment['submission']['files'][] = $submission_file;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $assessment
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving assessment details: ' . $e->getMessage()
    ]);
}
?> 