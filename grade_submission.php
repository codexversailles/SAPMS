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

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Only POST is allowed.'
    ]);
    exit;
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['submission_id']) || empty($data['submission_id'])) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Submission ID is required'
    ]);
    exit;
}

if (!isset($data['score'])) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Score is required'
    ]);
    exit;
}

$submission_id = intval($data['submission_id']);
$score = floatval($data['score']);
$feedback = isset($data['feedback']) ? $data['feedback'] : '';

try {
    // Update the submission with the score and feedback
    $update_query = "UPDATE student_submissions 
                    SET score = ?, teacher_feedback = ? 
                    WHERE id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("dsi", $score, $feedback, $submission_id);
    $result = $stmt->execute();
    
    if ($result) {
        // Get updated submission details
        $query = "SELECT ss.*, s.full_name as student_name, a.title as assessment_title, a.max_score
                 FROM student_submissions ss
                 JOIN students s ON ss.student_id = s.id
                 JOIN assessments a ON ss.assessment_id = a.id
                 WHERE ss.id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $submission_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $submission = $result->fetch_assoc();
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Submission graded successfully',
                'data' => [
                    'submission_id' => $submission_id,
                    'student_name' => $submission['student_name'],
                    'assessment_title' => $submission['assessment_title'],
                    'score' => $score,
                    'max_score' => $submission['max_score'],
                    'feedback' => $feedback
                ]
            ]);
        } else {
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Submission graded successfully',
                'data' => [
                    'submission_id' => $submission_id,
                    'score' => $score,
                    'feedback' => $feedback
                ]
            ]);
        }
    } else {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to grade submission'
        ]);
    }
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