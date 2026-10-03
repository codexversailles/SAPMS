<?php
session_start();
require_once 'db_connect.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in as a student
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in as student']);
    exit;
}

// Get assessment ID from query parameter
$assessment_id = $_GET['id'] ?? null;

if (!$assessment_id) {
    echo json_encode(['success' => false, 'message' => 'Assessment ID is required']);
    exit;
}

try {
    // Check if connection exists
    if (!isset($pdo)) {
        throw new Exception('Database connection not available');
    }
    
    // Get assessment details
    $query = "SELECT a.*, c.class_name 
              FROM assessments a
              JOIN classes c ON a.class_id = c.id
              WHERE a.id = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$assessment_id]);
    $assessment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assessment) {
        echo json_encode(['success' => false, 'message' => 'Assessment not found']);
        exit;
    }
    
    // Get associated files
    $file_query = "SELECT * FROM assessment_files WHERE assessment_id = ?";
    $file_stmt = $pdo->prepare($file_query);
    $file_stmt->execute([$assessment_id]);
    
    $assessment['files'] = [];
    while ($file = $file_stmt->fetch(PDO::FETCH_ASSOC)) {
        $assessment['files'][] = $file;
    }
    
    // Get associated links
    $link_query = "SELECT * FROM assessment_links WHERE assessment_id = ?";
    $link_stmt = $pdo->prepare($link_query);
    $link_stmt->execute([$assessment_id]);
    
    $assessment['links'] = [];
    while ($link = $link_stmt->fetch(PDO::FETCH_ASSOC)) {
        $assessment['links'][] = $link;
    }
    
    // Get student's current submission (if any)
    $submission_query = "SELECT * FROM student_submissions WHERE student_id = ? AND assessment_id = ?";
    $submission_stmt = $pdo->prepare($submission_query);
    $submission_stmt->execute([$_SESSION['student_id'], $assessment_id]);
    $submission = $submission_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($submission) {
        $assessment['submission'] = $submission;
        
        // Get submission files if available
        $submission_files_query = "SELECT * FROM submission_files WHERE submission_id = ?";
        $submission_files_stmt = $pdo->prepare($submission_files_query);
        $submission_files_stmt->execute([$submission['id']]);
        
        $assessment['submission']['files'] = [];
        while ($submission_file = $submission_files_stmt->fetch(PDO::FETCH_ASSOC)) {
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