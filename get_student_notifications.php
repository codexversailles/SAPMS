<?php
session_start();
require_once 'db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in'
    ]);
    exit;
}

$student_id = $_SESSION['student_id'];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';

try {
    // Count unread notifications for badge
    $unread_query = "SELECT COUNT(*) as unread_count FROM notifications 
                    WHERE student_id = ? AND is_read = 0";
    $unread_stmt = $pdo->prepare($unread_query);
    $unread_stmt->execute([$student_id]);
    $unread_count = $unread_stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
    
    // Build query
    $query = "SELECT n.*, c.class_name 
              FROM notifications n
              LEFT JOIN classes c ON n.class_id = c.id
              WHERE n.student_id = ?";
    
    if ($unread_only) {
        $query .= " AND n.is_read = 0";
    }
    
    // Fix: Embed the LIMIT and OFFSET values directly into the query instead of using parameter binding
    $query .= " ORDER BY n.created_at DESC LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$student_id]); // Removed the limit and offset parameters
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format each notification with time ago
    foreach ($notifications as &$notification) {
        $notification['formatted_time'] = formatTimeAgo($notification['created_at']);
    }
    
    echo json_encode([
        'success' => true,
        'unread_count' => $unread_count,
        'notifications' => $notifications
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving notifications: ' . $e->getMessage(),
        'error_details' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

// Helper function to format time ago
function formatTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 172800) {
        return 'Yesterday';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}
?> 