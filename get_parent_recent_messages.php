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

// Get limit parameter (default to 10)
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Fetch recent teacher messages
try {
    $stmt = $pdo->prepare("
        SELECT 
            ptm.*,
            t.full_name as teacher_name,
            c.class_name,
            c.class_code,
            CASE
                WHEN ptm.created_at > DATE_SUB(NOW(), INTERVAL 1 DAY) THEN CONCAT('Today, ', DATE_FORMAT(ptm.created_at, '%h:%i %p'))
                WHEN ptm.created_at > DATE_SUB(NOW(), INTERVAL 2 DAY) THEN CONCAT('Yesterday, ', DATE_FORMAT(ptm.created_at, '%h:%i %p'))
                ELSE DATE_FORMAT(ptm.created_at, '%b %d, %Y, %h:%i %p')
            END AS formatted_time,
            TIMESTAMPDIFF(MINUTE, ptm.created_at, NOW()) AS minutes_ago
        FROM parent_teacher_messages ptm
        JOIN teachers t ON ptm.teacher_id = t.id
        JOIN classes c ON ptm.class_id = c.id
        WHERE ptm.parent_id = ?
        ORDER BY ptm.created_at DESC
        LIMIT ?
    ");
    
    $stmt->execute([$parentId, $limit]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the messages for display
    $formattedMessages = [];
    foreach ($messages as $message) {
        $minutesAgo = $message['minutes_ago'];
        
        // Format the time ago text
        if ($minutesAgo < 1) {
            $timeAgo = 'Just now';
        } elseif ($minutesAgo < 60) {
            $timeAgo = $minutesAgo . ' min ago';
        } elseif ($minutesAgo < 1440) {
            $hoursAgo = floor($minutesAgo / 60);
            $timeAgo = $hoursAgo . ' hour' . ($hoursAgo > 1 ? 's' : '') . ' ago';
        } else {
            $timeAgo = $message['formatted_time'];
        }
        
        $formattedMessages[] = [
            'id' => $message['id'],
            'parent_id' => $message['parent_id'],
            'teacher_id' => $message['teacher_id'],
            'class_id' => $message['class_id'],
            'sender_type' => $message['sender_type'],
            'message' => $message['message'],
            'is_read' => (bool)$message['is_read'],
            'created_at' => $message['created_at'],
            'time_ago' => $timeAgo,
            'teacher_name' => $message['teacher_name'],
            'class_name' => $message['class_name'],
            'class_code' => $message['class_code']
        ];
    }
    
    // Get unread message counts
    $stmt = $pdo->prepare("
        SELECT 
            teacher_id, 
            class_id, 
            COUNT(*) AS unread_count
        FROM parent_teacher_messages
        WHERE parent_id = ? AND sender_type = 'teacher' AND is_read = 0
        GROUP BY teacher_id, class_id
    ");
    
    $stmt->execute([$parentId]);
    $unreadCounts = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $key = $row['teacher_id'] . '_' . $row['class_id'];
        $unreadCounts[$key] = (int)$row['unread_count'];
    }
    
    // Get child info for this parent
    $stmt = $pdo->prepare("
        SELECT 
            pc.id,
            pc.student_id,
            s.full_name as student_name
        FROM parent_children pc
        JOIN students s ON pc.student_id = s.student_id
        WHERE pc.parent_id = ?
    ");
    
    $stmt->execute([$parentId]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'messages' => $formattedMessages,
        'unread_counts' => $unreadCounts,
        'children' => $children
    ]);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error', 'debug' => $e->getMessage()]);
} 