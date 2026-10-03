<?php
// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Include database connection
require_once 'db_connect.php';

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Check if parent_id and class_id are provided
if (!isset($data['parent_id']) || !isset($data['class_id'])) {
    echo json_encode(['success' => false, 'message' => 'Parent ID and Class ID are required']);
    exit;
}

try {
    $teacherId = $_SESSION['teacher_id'];
    $parentId = $data['parent_id'];
    $classId = $data['class_id'];
    
    // Mark messages from this parent in this class as read
    $stmt = $pdo->prepare("
        UPDATE parent_teacher_messages
        SET is_read = 1
        WHERE teacher_id = :teacher_id
        AND parent_id = :parent_id
        AND class_id = :class_id
        AND sender_type = 'parent'
        AND is_read = 0
    ");
    
    $stmt->execute([
        'teacher_id' => $teacherId,
        'parent_id' => $parentId,
        'class_id' => $classId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Messages marked as read',
        'updated_rows' => $stmt->rowCount()
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 