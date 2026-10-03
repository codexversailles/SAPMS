<?php
session_start();
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header to return JSON
header('Content-Type: application/json');

try {
    // Get class_id from query parameters
    $class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
    
    if (!$class_id) {
        throw new Exception('Class ID is required');
    }

    // Check if the current user is a student
    $is_student = isset($_SESSION['student_id']) && !isset($_SESSION['teacher_id']);

    // Prepare the query to get assessments with file count
    $query = "SELECT a.*, 
              COUNT(DISTINCT af.id) as file_count,
              COUNT(DISTINCT ss.id) as submission_count
              FROM assessments a
              LEFT JOIN assessment_files af ON a.id = af.assessment_id
              LEFT JOIN student_submissions ss ON a.id = ss.assessment_id
              WHERE a.class_id = ?";
    
    // For students, only show assessments that haven't expired
    if ($is_student) {
        $query .= " AND (a.due_date IS NULL OR a.due_date > NOW())";
    }
    
    $query .= " GROUP BY a.id
              ORDER BY a.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$class_id]);
    $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For each assessment, get its files and add status information
    foreach ($assessments as &$assessment) {
        $file_query = "SELECT * FROM assessment_files WHERE assessment_id = ?";
        $file_stmt = $pdo->prepare($file_query);
        $file_stmt->execute([$assessment['id']]);
        $assessment['files'] = $file_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add status for due date
        if (!empty($assessment['due_date'])) {
            $due_timestamp = strtotime($assessment['due_date']);
            $current_timestamp = time();
            $assessment['is_expired'] = ($due_timestamp < $current_timestamp);
            $assessment['time_remaining'] = $due_timestamp - $current_timestamp;
        } else {
            $assessment['is_expired'] = false;
            $assessment['time_remaining'] = null;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $assessments
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?> 