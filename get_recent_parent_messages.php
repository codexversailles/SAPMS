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

try {
    $teacherId = $_SESSION['teacher_id'];
    
    // Get recent parent messages
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
            c.class_name
        FROM parent_teacher_messages ptm
        JOIN parents p ON ptm.parent_id = p.id
        JOIN classes c ON ptm.class_id = c.id
        WHERE ptm.teacher_id = :teacher_id
        ORDER BY ptm.created_at DESC
        LIMIT 10
    ");
    
    $stmt->execute(['teacher_id' => $teacherId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unread message counts grouped by parent_id and class_id
    $stmt = $pdo->prepare("
        SELECT 
            parent_id,
            class_id,
            COUNT(*) as unread_count
        FROM parent_teacher_messages
        WHERE teacher_id = :teacher_id
        AND sender_type = 'parent'
        AND is_read = 0
        GROUP BY parent_id, class_id
    ");
    
    $stmt->execute(['teacher_id' => $teacherId]);
    $unreadCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to associative array for easier access in JavaScript
    $unreadCountsAssoc = [];
    foreach ($unreadCounts as $count) {
        $key = $count['parent_id'] . '_' . $count['class_id'];
        $unreadCountsAssoc[$key] = $count['unread_count'];
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'unread_counts' => $unreadCountsAssoc
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 