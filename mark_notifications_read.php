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

// Get JSON data from the request body
$data = json_decode(file_get_contents('php://input'), true);
$student_id = $_SESSION['student_id'];

try {
    // If notification_id is provided, mark specific notification as read
    if (isset($data['notification_id'])) {
        $notification_id = $data['notification_id'];
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 
                               WHERE id = ? AND student_id = ?");
        $stmt->execute([$notification_id, $student_id]);
        
        $affected = $stmt->rowCount();
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read',
            'affected' => $affected
        ]);
    } 
    // Otherwise mark all notifications as read
    else {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 
                               WHERE student_id = ? AND is_read = 0");
        $stmt->execute([$student_id]);
        
        $affected = $stmt->rowCount();
        
        echo json_encode([
            'success' => true,
            'message' => 'All notifications marked as read',
            'affected' => $affected
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error marking notifications as read: ' . $e->getMessage()
    ]);
}
?> 