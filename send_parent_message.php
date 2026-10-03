<?php
session_start();
header('Content-Type: application/json');

// Check if parent is logged in
if (!isset($_SESSION['parent_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get parent ID from session
$parentId = $_SESSION['parent_id'];

// Get database connection
require_once 'db_connect.php';

// Get request data
$requestData = json_decode(file_get_contents('php://input'), true);

// Validate request data
if (!isset($requestData['teacher_id']) || !isset($requestData['class_id']) || !isset($requestData['message'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$teacherId = $requestData['teacher_id'];
$classId = $requestData['class_id'];
$message = trim($requestData['message']);

// Validate message
if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
    exit;
}

// Verify that the class exists and the teacher is assigned to it
$stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
$stmt->execute([$classId, $teacherId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Invalid class or teacher']);
    exit;
}

// Check if the parent has a child in this class
$stmt = $pdo->prepare("
    SELECT pc.id 
    FROM parent_children pc
    JOIN students s ON pc.student_id = s.student_id
    JOIN class_students cs ON cs.student_id = s.id
    WHERE pc.parent_id = ? AND cs.class_id = ?
");
$stmt->execute([$parentId, $classId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You do not have a child in this class']);
    exit;
}

// Insert the message
try {
    $stmt = $pdo->prepare("
        INSERT INTO parent_teacher_messages (parent_id, teacher_id, class_id, sender_type, message)
        VALUES (?, ?, ?, 'parent', ?)
    ");
    $success = $stmt->execute([$parentId, $teacherId, $classId, $message]);
    
    if ($success) {
        $messageId = $pdo->lastInsertId();
        echo json_encode([
            'success' => true, 
            'message' => 'Message sent successfully',
            'data' => [
                'id' => $messageId,
                'parent_id' => $parentId,
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'sender_type' => 'parent',
                'message' => $message,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error', 'debug' => $e->getMessage()]);
} 