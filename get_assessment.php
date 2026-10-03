<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Get assessment ID from query parameter
$assessment_id = $_GET['id'] ?? null;

if (!$assessment_id) {
    echo json_encode(['success' => false, 'message' => 'Assessment ID is required']);
    exit;
}

try {
    // Get assessment details
    $query = "SELECT a.*, c.class_name, c.class_code 
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
    
    // Add due date info
    if (!empty($assessment['due_date'])) {
        $due_timestamp = strtotime($assessment['due_date']);
        $current_timestamp = time();
        $assessment['is_expired'] = ($due_timestamp < $current_timestamp);
        $assessment['time_remaining'] = $due_timestamp - $current_timestamp;
    } else {
        $assessment['is_expired'] = false;
        $assessment['time_remaining'] = null;
    }
    
    // Get associated files
    $file_query = "SELECT id, file_name, file_path, file_type, file_size, uploaded_at
                  FROM assessment_files
                  WHERE assessment_id = ?";
    $file_stmt = $pdo->prepare($file_query);
    $file_stmt->execute([$assessment_id]);
    $assessment['files'] = $file_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get student submissions
    $submissions_query = "SELECT ss.*, s.full_name as student_name, s.email as student_email
                        FROM student_submissions ss
                        JOIN students s ON ss.student_id = s.id
                        WHERE ss.assessment_id = ?
                        ORDER BY ss.submission_date DESC";
    $submissions_stmt = $pdo->prepare($submissions_query);
    $submissions_stmt->execute([$assessment_id]);
    $assessment['submissions'] = $submissions_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $assessment
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving assessment: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?> 