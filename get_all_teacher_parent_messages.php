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
    
    // Get all parent messages with additional info
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
        LIMIT 100
    ");
    
    $stmt->execute(['teacher_id' => $teacherId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get children info for each parent
    foreach ($messages as &$message) {
        // Get children info
        $childrenStmt = $pdo->prepare("
            SELECT 
                pc.student_id,
                s.full_name
            FROM parent_children pc
            JOIN students s ON pc.student_id = s.student_id
            WHERE pc.parent_id = :parent_id
        ");
        
        $childrenStmt->execute(['parent_id' => $message['parent_id']]);
        $children = $childrenStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format children names
        $childrenNames = [];
        foreach ($children as $child) {
            $childrenNames[] = $child['full_name'];
        }
        
        $message['children_info'] = !empty($childrenNames) ? implode(', ', $childrenNames) : '';
        
        // Format time
        $createdAt = new DateTime($message['created_at']);
        $message['formatted_time'] = $createdAt->format('g:i A');
        $message['formatted_date'] = $createdAt->format('M d, Y');
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 