<?php
session_start();
require_once 'config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$teacherId = $_SESSION['teacher_id'];

try {
    // Get recent messages from students across all classes
    // Limiting to the most recent 10 messages per student
    $stmt = $pdo->prepare("
        WITH RecentMessages AS (
            SELECT 
                m.*,
                ROW_NUMBER() OVER (PARTITION BY m.student_id, m.class_id ORDER BY m.created_at DESC) as row_num
            FROM teacher_student_messages m
            WHERE m.teacher_id = ?
        )
        SELECT 
            rm.id,
            rm.student_id,
            rm.class_id,
            s.full_name as student_name,
            c.class_name,
            rm.message,
            rm.sender_type,
            rm.is_read,
            rm.created_at
        FROM RecentMessages rm
        JOIN students s ON rm.student_id = s.id
        JOIN classes c ON rm.class_id = c.id
        WHERE row_num = 1
        ORDER BY rm.created_at DESC
        LIMIT 15
    ");
    
    $stmt->execute([$teacherId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format messages with additional attributes for display
    $formattedMessages = [];
    foreach ($messages as $message) {
        $formattedMessage = $message;
        $formattedMessage['formatted_time'] = date('h:i A', strtotime($message['created_at']));
        $formattedMessage['formatted_date'] = date('M j, Y', strtotime($message['created_at']));
        $formattedMessage['time_ago'] = getTimeAgo(strtotime($message['created_at']));
        $formattedMessages[] = $formattedMessage;
    }
    
    // Count unread messages per student
    $stmt = $pdo->prepare("
        SELECT student_id, class_id, COUNT(*) as unread_count
        FROM teacher_student_messages
        WHERE teacher_id = ? AND sender_type = 'student' AND is_read = FALSE
        GROUP BY student_id, class_id
    ");
    
    $stmt->execute([$teacherId]);
    $unreadCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $unreadData = [];
    foreach ($unreadCounts as $count) {
        $key = $count['student_id'] . '_' . $count['class_id'];
        $unreadData[$key] = $count['unread_count'];
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $formattedMessages,
        'unread_counts' => $unreadData
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching messages',
        'error' => $e->getMessage()
    ]);
}

// Helper function to format time ago
function getTimeAgo($timestamp) {
    $current_time = time();
    $time_difference = $current_time - $timestamp;
    
    if ($time_difference < 60) {
        return "Just now";
    } elseif ($time_difference < 3600) {
        $minutes = floor($time_difference / 60);
        return $minutes . ($minutes == 1 ? ' minute ago' : ' minutes ago');
    } elseif ($time_difference < 86400) {
        $hours = floor($time_difference / 3600);
        return $hours . ($hours == 1 ? ' hour ago' : ' hours ago');
    } elseif ($time_difference < 604800) {
        $days = floor($time_difference / 86400);
        return $days . ($days == 1 ? ' day ago' : ' days ago');
    } else {
        return date('M j, Y', $timestamp);
    }
} 