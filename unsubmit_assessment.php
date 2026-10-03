<?php
session_start();
require_once 'db_connect.php';

// Set header to return JSON
header('Content-Type: application/json');

// Enable error reporting for troubleshooting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in as student
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in as student']);
    exit;
}

$student_id = $_SESSION['student_id'];
$assessment_id = $_POST['assessment_id'] ?? null;

// Validate required field
if (!$assessment_id) {
    echo json_encode(['success' => false, 'message' => 'Assessment ID is required']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // First, check if the submission exists, belongs to this student, and is not graded
    $check_query = "SELECT ss.id, ss.score, sf.file_path 
                    FROM student_submissions ss
                    LEFT JOIN submission_files sf ON sf.submission_id = ss.id
                    WHERE ss.student_id = ? AND ss.assessment_id = ?";
    
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([$student_id, $assessment_id]);
    
    $rows = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) == 0) {
        throw new Exception("No submission found for this assessment");
    }
    
    // Check if submission is graded
    if ($rows[0]['score'] !== null) {
        throw new Exception("Cannot unsubmit a graded assignment");
    }
    
    // Get the submission ID and file paths
    $submission_id = null;
    $file_paths = [];
    
    foreach ($rows as $row) {
        if ($submission_id === null) {
            $submission_id = $row['id'];
        }
        
        if (!empty($row['file_path'])) {
            $file_paths[] = $row['file_path'];
        }
    }
    
    // Delete physical files
    foreach ($file_paths as $file_path) {
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Delete submission files from database
    if ($submission_id) {
        $delete_files_query = "DELETE FROM submission_files WHERE submission_id = ?";
        $delete_files_stmt = $pdo->prepare($delete_files_query);
        $delete_files_stmt->execute([$submission_id]);
        
        // Delete the submission record
        $delete_submission_query = "DELETE FROM student_submissions WHERE id = ?";
        $delete_submission_stmt = $pdo->prepare($delete_submission_query);
        $delete_submission_stmt->execute([$submission_id]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Assignment Unsubmitted"
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error unsubmitting assessment: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?> 