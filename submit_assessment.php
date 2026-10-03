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

// Validate required fields
if (!$assessment_id) {
    echo json_encode(['success' => false, 'message' => 'Assessment ID is required']);
    exit;
}

// Check if a file was uploaded
if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] != 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'No file uploaded or upload error',
        'error_details' => $_FILES['submission_file']['error'] ?? 'No file specified'
    ]);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Check if student already has a submission for this assessment
    $check_query = "SELECT id FROM student_submissions WHERE student_id = ? AND assessment_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $student_id, $assessment_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing submission
        $submission = $result->fetch_assoc();
        $submission_id = $submission['id'];
        
        $update_query = "UPDATE student_submissions SET submission_date = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $submission_id);
        $update_stmt->execute();
    } else {
        // Create new submission
        $insert_query = "INSERT INTO student_submissions (assessment_id, student_id, submission_date) VALUES (?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("ii", $assessment_id, $student_id);
        $insert_stmt->execute();
        $submission_id = $conn->insert_id;
    }
    
    // Handle file upload
    $file = $_FILES['submission_file'];
    $file_name = $file['name'];
    $file_type = $file['type'];
    $file_size = $file['size'];
    $tmp_name = $file['tmp_name'];
    
    // Create upload directory if it doesn't exist
    $upload_dir = 'uploads/submissions/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $unique_filename = uniqid() . '_' . $file_name;
    $file_path = $upload_dir . $unique_filename;
    
    // Move uploaded file
    if (move_uploaded_file($tmp_name, $file_path)) {
        // Insert file record
        $file_query = "INSERT INTO submission_files (submission_id, file_name, file_path, file_type, file_size) 
                      VALUES (?, ?, ?, ?, ?)";
        $file_stmt = $conn->prepare($file_query);
        $file_stmt->bind_param("isssi", $submission_id, $file_name, $file_path, $file_type, $file_size);
        $file_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Assignment Successfully Submitted!",
            'submission_id' => $submission_id
        ]);
    } else {
        throw new Exception("Failed to move uploaded file");
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting assessment: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?> 