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

// Check if class_id is provided
if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Class ID is required'
    ]);
    exit;
}

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to view class details'
    ]);
    exit;
}

// Get IDs from the request and session
$class_id = intval($_GET['class_id']);
$student_id = intval($_SESSION['student_id']);

// Initialize response array
$response = [
    'success' => true,
    'class_id' => $class_id,
    'lessons' => [],
    'assessments' => []
];

try {
    // Log connection status
    if (!isset($pdo)) {
        throw new Exception('Database connection not available');
    }
    
    // Get lessons for the class
    $stmt = $pdo->prepare("SELECT id, title, description, created_at 
                           FROM lessons 
                           WHERE class_id = ? 
                           ORDER BY created_at DESC");
    $stmt->execute([$class_id]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($lessons as $row) {
        $response['lessons'][] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Get assessments for the class
    $stmt = $pdo->prepare("SELECT id, title, description, due_date, created_at, max_score 
                           FROM assessments 
                           WHERE class_id = ? 
                           ORDER BY due_date ASC, created_at DESC");
    $stmt->execute([$class_id]);
    $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($assessments as $row) {
        $assessment = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'due_date' => $row['due_date'],
            'created_at' => $row['created_at'],
            'max_score' => $row['max_score']
        ];
        
        // Get student's submission for this assessment if it exists
        $submissionStmt = $pdo->prepare("SELECT id, submission_date, score, teacher_feedback 
                                         FROM student_submissions 
                                         WHERE assessment_id = ? AND student_id = ?");
        $submissionStmt->execute([$row['id'], $student_id]);
        $submission = $submissionStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($submission) {
            $assessment['student_submission'] = [
                'id' => $submission['id'],
                'submission_date' => $submission['submission_date'],
                'score' => $submission['score'],
                'teacher_feedback' => $submission['teacher_feedback']
            ];
        }
        
        $response['assessments'][] = $assessment;
    }
    
    // Return the response
    echo json_encode($response);
} catch (Exception $e) {
    // Return detailed error message for debugging
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve class details',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} 