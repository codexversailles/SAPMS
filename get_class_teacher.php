<?php
session_start();
require_once 'db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in'
    ]);
    exit;
}

// Get class ID from query parameter
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if (!$class_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Class ID is required'
    ]);
    exit;
}

try {
    // Fetch teacher details for the class
    $query = "SELECT t.id as teacher_id, t.full_name as teacher_name, t.email as teacher_email 
              FROM classes c
              JOIN teachers t ON c.teacher_id = t.id
              WHERE c.id = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$class_id]);
    $teacherData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$teacherData) {
        echo json_encode([
            'success' => false,
            'message' => 'Teacher not found for this class'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'teacher_id' => $teacherData['teacher_id'],
        'teacher_name' => $teacherData['teacher_name'],
        'teacher_email' => $teacherData['teacher_email']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching teacher details: ' . $e->getMessage(),
        'error_details' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
} 