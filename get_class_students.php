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

// Get class ID from request
$classId = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;

if (!$classId) {
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit;
}

try {
    // Fetch the class to verify it belongs to the current teacher
    $stmt = $pdo->prepare("
        SELECT * FROM classes 
        WHERE id = ? AND teacher_id = ?
    ");
    $stmt->execute([$classId, $_SESSION['teacher_id']]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Class not found or access denied']);
        exit;
    }
    
    // Fetch enrolled students for this class
    $stmt = $pdo->prepare("
        SELECT s.id, s.full_name, s.email, s.student_id, cs.enrolled_at, cs.status
        FROM class_students cs
        JOIN students s ON cs.student_id = s.id
        WHERE cs.class_id = ?
        ORDER BY s.full_name
    ");
    $stmt->execute([$classId]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'students' => $students
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching students: ' . $e->getMessage()
    ]);
}
?> 