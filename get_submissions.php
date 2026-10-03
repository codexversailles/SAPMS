<?php
// Prevent any output before our JSON response
ob_start();

// Include database connection
require_once 'config/database.php';

// Set error handling to catch any unexpected issues
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Return JSON error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'PHP Error: ' . $errstr,
        'debug' => [
            'file' => $errfile,
            'line' => $errline
        ]
    ]);
    exit;
});

// Set headers for JSON response
header('Content-Type: application/json');

// Check if assessment_id is provided
if (!isset($_GET['assessment_id']) || empty($_GET['assessment_id'])) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Assessment ID is required'
    ]);
    exit;
}

$assessment_id = intval($_GET['assessment_id']);

try {
    // Get assessment details
    $assessment_query = "SELECT a.*, c.class_name 
                        FROM assessments a 
                        JOIN classes c ON a.class_id = c.id 
                        WHERE a.id = ?";
    
    $assessment_stmt = $conn->prepare($assessment_query);
    $assessment_stmt->bind_param("i", $assessment_id);
    $assessment_stmt->execute();
    $assessment_result = $assessment_stmt->get_result();
    
    if ($assessment_result->num_rows === 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Assessment not found'
        ]);
        exit;
    }
    
    $assessment = $assessment_result->fetch_assoc();
    
    // Get submissions for this assessment
    $submissions_query = "SELECT ss.*, s.full_name as student_name, s.email as student_email, s.student_id as student_id_number
                         FROM student_submissions ss
                         JOIN students s ON ss.student_id = s.id
                         WHERE ss.assessment_id = ?";
    
    $submissions_stmt = $conn->prepare($submissions_query);
    $submissions_stmt->bind_param("i", $assessment_id);
    $submissions_stmt->execute();
    $submissions_result = $submissions_stmt->get_result();
    
    $submissions = [];
    
    while ($submission = $submissions_result->fetch_assoc()) {
        // Get files for this submission
        $files_query = "SELECT * FROM submission_files WHERE submission_id = ?";
        $files_stmt = $conn->prepare($files_query);
        $files_stmt->bind_param("i", $submission['id']);
        $files_stmt->execute();
        $files_result = $files_stmt->get_result();
        
        $files = [];
        while ($file = $files_result->fetch_assoc()) {
            $files[] = $file;
        }
        
        $submission['files'] = $files;
        $submissions[] = $submission;
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'id' => $assessment['id'],
            'title' => $assessment['title'],
            'description' => $assessment['description'],
            'due_date' => $assessment['due_date'],
            'max_score' => $assessment['max_score'],
            'class_name' => $assessment['class_name'],
            'submissions' => $submissions
        ]
    ];
    
    // Clear any buffered output before sending JSON
    ob_end_clean();
    
    // Encode with options to catch any JSON encoding errors
    $json = json_encode($response, JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new Exception('JSON encoding failed: ' . json_last_error_msg());
    }
    
    echo $json;
    
} catch (Exception $e) {
    // Clear any buffered output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
    ]);
}
?> 