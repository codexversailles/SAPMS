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
    
    // First, get the distinct parent_id and class_id combinations from messages
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            ptm.parent_id,
            ptm.class_id
        FROM parent_teacher_messages ptm
        WHERE ptm.teacher_id = :teacher_id
    ");
    
    $stmt->execute(['teacher_id' => $teacherId]);
    $parentClassCombos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $messages = [];
    
    // For each parent-class combination, get the most recent message
    foreach ($parentClassCombos as $combo) {
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
            AND ptm.parent_id = :parent_id
            AND ptm.class_id = :class_id
            ORDER BY ptm.created_at DESC
            LIMIT 1
        ");
        
        $stmt->execute([
            'teacher_id' => $teacherId,
            'parent_id' => $combo['parent_id'],
            'class_id' => $combo['class_id']
        ]);
        
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($message) {
            // Get the children info for this parent
            $childrenStmt = $pdo->prepare("
                SELECT 
                    s.full_name,
                    pc.relationship
                FROM parent_children pc
                JOIN students s ON pc.student_id = s.student_id
                WHERE pc.parent_id = :parent_id
            ");
            
            $childrenStmt->execute(['parent_id' => $message['parent_id']]);
            $children = $childrenStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $childrenInfo = [];
            foreach ($children as $child) {
                $childrenInfo[] = $child['full_name'] . ' (' . $child['relationship'] . ')';
            }
            
            $message['children_info'] = implode(', ', $childrenInfo);
            $messages[] = $message;
        }
    }
    
    // Sort messages by created_at in descending order
    usort($messages, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Limit to 10 most recent messages
    $messages = array_slice($messages, 0, 10);
    
    // Get unread message counts
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
    
    // Create an associative array for easier lookup in JavaScript
    $unreadCountsAssoc = [];
    foreach ($unreadCounts as $count) {
        $key = $count['parent_id'] . '_' . $count['class_id'];
        $unreadCountsAssoc[$key] = $count['unread_count'];
    }
    
    // Format relative time for each message
    foreach ($messages as &$message) {
        $messageDate = new DateTime($message['created_at']);
        $now = new DateTime();
        $interval = $now->diff($messageDate);
        
        if ($interval->days > 7) {
            $message['formatted_time'] = $messageDate->format('M j, Y');
        } elseif ($interval->days > 0) {
            $message['formatted_time'] = $interval->days . ' day' . ($interval->days > 1 ? 's' : '') . ' ago';
        } elseif ($interval->h > 0) {
            $message['formatted_time'] = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
        } elseif ($interval->i > 0) {
            $message['formatted_time'] = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
        } else {
            $message['formatted_time'] = 'Just now';
        }
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