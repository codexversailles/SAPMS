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

// Check if parent_id and class_id are provided
if (!isset($_GET['parent_id']) || !isset($_GET['class_id'])) {
    echo json_encode(['success' => false, 'message' => 'Parent ID and Class ID are required']);
    exit;
}

try {
    $teacherId = $_SESSION['teacher_id'];
    $parentId = $_GET['parent_id'];
    $classId = $_GET['class_id'];
    
    // Get all messages between this teacher and parent for the specified class
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
        WHERE ptm.teacher_id = :teacher_id
        AND ptm.parent_id = :parent_id
        AND ptm.class_id = :class_id
        ORDER BY ptm.created_at ASC
    ");
    
    $stmt->execute([
        'teacher_id' => $teacherId,
        'parent_id' => $parentId,
        'class_id' => $classId
    ]);
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get parent and class information
    $stmt = $pdo->prepare("
        SELECT 
            p.id AS parent_id,
            p.parent_full_name,
            c.id AS class_id,
            c.class_name
        FROM parents p, classes c
        WHERE p.id = :parent_id
        AND c.id = :class_id
    ");
    
    $stmt->execute([
        'parent_id' => $parentId,
        'class_id' => $classId
    ]);
    
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Mark messages as read
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
        'messages' => $messages,
        'info' => $info
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 