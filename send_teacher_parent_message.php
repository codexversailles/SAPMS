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

// Check if required fields are provided
if (!isset($data['parent_id']) || !isset($data['class_id']) || !isset($data['message']) || empty($data['message'])) {
    echo json_encode(['success' => false, 'message' => 'Parent ID, Class ID, and message are required']);
    exit;
}

try {
    $teacherId = $_SESSION['teacher_id'];
    $parentId = $data['parent_id'];
    $classId = $data['class_id'];
    $message = trim($data['message']);
    
    // Insert the message
    $stmt = $pdo->prepare("
        INSERT INTO parent_teacher_messages (
            parent_id,
            teacher_id,
            class_id,
            sender_type,
            message,
            is_read,
            created_at
        ) VALUES (
            :parent_id,
            :teacher_id,
            :class_id,
            'teacher',
            :message,
            0,
            NOW()
        )
    ");
    
    $stmt->execute([
        'parent_id' => $parentId,
        'teacher_id' => $teacherId,
        'class_id' => $classId,
        'message' => $message
    ]);
    
    // Get the inserted message with additional info
    $messageId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        SELECT 
            ptm.id,
            ptm.parent_id,
            ptm.teacher_id,
            ptm.class_id,
            ptm.sender_type,
            ptm.message,
            ptm.is_read,
            ptm.created_at,
            p.parent_full_name,
            t.full_name AS teacher_name,
            c.class_name
        FROM parent_teacher_messages ptm
        JOIN parents p ON ptm.parent_id = p.id
        JOIN teachers t ON ptm.teacher_id = t.id
        JOIN classes c ON ptm.class_id = c.id
        WHERE ptm.id = :message_id
    ");
    
    $stmt->execute(['message_id' => $messageId]);
    $messageData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully',
        'message_data' => $messageData
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 