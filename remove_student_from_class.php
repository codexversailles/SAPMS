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

// Get data from request
$data = json_decode(file_get_contents('php://input'), true);
$classId = isset($data['class_id']) ? intval($data['class_id']) : null;
$studentId = isset($data['student_id']) ? intval($data['student_id']) : null;

if (!$classId || !$studentId) {
    echo json_encode(['success' => false, 'message' => 'Class ID and Student ID are required']);
    exit;
}

try {
    // Verify the class belongs to the current teacher
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
    
    // Check if the student is enrolled in the class
    $stmt = $pdo->prepare("
        SELECT * FROM class_students 
        WHERE class_id = ? AND student_id = ?
    ");
    $stmt->execute([$classId, $studentId]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enrollment) {
        echo json_encode(['success' => false, 'message' => 'Student not enrolled in this class']);
        exit;
    }
    
    // Remove the student from the class
    $stmt = $pdo->prepare("
        DELETE FROM class_students 
        WHERE class_id = ? AND student_id = ?
    ");
    $stmt->execute([$classId, $studentId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Student removed from class successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error removing student: ' . $e->getMessage()
    ]);
}
?> 