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

// Get lesson ID from query parameter
$lesson_id = $_GET['id'] ?? null;

if (!$lesson_id) {
    echo json_encode(['success' => false, 'message' => 'Lesson ID is required']);
    exit;
}

try {
    // Check if connection exists
    if (!isset($conn)) {
        throw new Exception('Database connection not available');
    }
    
    // Get lesson details
    $query = "SELECT l.*, c.class_name 
              FROM lessons l
              JOIN classes c ON l.class_id = c.id
              WHERE l.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $lesson_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $lesson = $result->fetch_assoc();
    
    if (!$lesson) {
        echo json_encode(['success' => false, 'message' => 'Lesson not found']);
        exit;
    }
    
    // Get associated files
    $file_query = "SELECT * FROM lesson_files WHERE lesson_id = ?";
    $file_stmt = $conn->prepare($file_query);
    $file_stmt->bind_param("i", $lesson_id);
    $file_stmt->execute();
    $file_result = $file_stmt->get_result();
    
    $lesson['files'] = [];
    while ($file = $file_result->fetch_assoc()) {
        $lesson['files'][] = $file;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $lesson
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving lesson details: ' . $e->getMessage()
    ]);
}
?> 