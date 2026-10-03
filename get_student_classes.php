<?php
session_start();
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

try {
    // Get all classes the student is enrolled in
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.class_name,
            c.class_code,
            c.created_at,
            t.full_name as teacher_name 
        FROM classes c
        JOIN class_students cs ON c.id = cs.class_id
        JOIN teachers t ON c.teacher_id = t.id
        WHERE cs.student_id = ? AND cs.status = 'active' AND c.is_active = 1
        ORDER BY c.created_at DESC
    ");
    
    $stmt->execute([$_SESSION['student_id']]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug information
    $debug = [
        'student_id' => $_SESSION['student_id'],
        'classes_count' => count($classes),
        'classes' => $classes
    ];

    echo json_encode([
        'success' => true, 
        'classes' => $classes,
        'debug' => $debug
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