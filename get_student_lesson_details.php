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
    if (!isset($pdo)) {
        throw new Exception('Database connection not available');
    }
    
    // Get lesson details
    $query = "SELECT l.*, c.class_name 
              FROM lessons l
              JOIN classes c ON l.class_id = c.id
              WHERE l.id = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$lesson_id]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lesson) {
        echo json_encode(['success' => false, 'message' => 'Lesson not found']);
        exit;
    }
    
    // Get associated files
    $file_query = "SELECT * FROM lesson_files WHERE lesson_id = ?";
    $file_stmt = $pdo->prepare($file_query);
    $file_stmt->execute([$lesson_id]);
    
    $lesson['files'] = [];
    while ($file = $file_stmt->fetch(PDO::FETCH_ASSOC)) {
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