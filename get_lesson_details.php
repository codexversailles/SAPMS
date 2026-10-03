<?php
session_start();
require_once 'config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in as a teacher
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in as teacher']);
    exit;
}

// Get lesson ID from query parameter
$lesson_id = $_GET['id'] ?? null;

if (!$lesson_id) {
    echo json_encode(['success' => false, 'message' => 'Lesson ID is required']);
    exit;
}

try {
    // Get lesson details
    $query = "SELECT l.*, c.class_name, c.class_code 
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
    $lesson['files'] = $file_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $lesson
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving lesson details: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?> 