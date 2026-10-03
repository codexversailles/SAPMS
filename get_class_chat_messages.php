<?php
session_start();
require_once 'config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Check if class_id is provided
if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit;
}

$classId = $_GET['class_id'];
$studentId = $_SESSION['student_id'];

try {
    // First verify that the student is enrolled in this class
    $stmt = $pdo->prepare("
        SELECT 1 FROM class_students 
        WHERE class_id = ? AND student_id = ? AND status = 'active'
    ");
    $stmt->execute([$classId, $studentId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'You are not enrolled in this class']);
        exit;
    }
    
    // Get chat messages for this class
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.message,
            m.created_at,
            m.sender_type,
            m.sender_id,
            CASE 
                WHEN m.sender_type = 'student' THEN s.full_name
                WHEN m.sender_type = 'teacher' THEN t.full_name
                ELSE 'Unknown User'
            END as sender_name
        FROM class_chat_messages m
        LEFT JOIN students s ON m.sender_type = 'student' AND m.sender_id = s.id
        LEFT JOIN teachers t ON m.sender_type = 'teacher' AND m.sender_id = t.id
        WHERE m.class_id = ?
        ORDER BY m.created_at ASC
        LIMIT 100
    ");
    
    $stmt->execute([$classId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format messages with additional attributes for display
    $formattedMessages = [];
    foreach ($messages as $message) {
        $formattedMessage = $message;
        $formattedMessage['is_own'] = ($message['sender_type'] == 'student' && $message['sender_id'] == $studentId);
        $formattedMessage['formatted_time'] = date('h:i A', strtotime($message['created_at']));
        $formattedMessage['formatted_date'] = date('M j, Y', strtotime($message['created_at']));
        $formattedMessages[] = $formattedMessage;
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $formattedMessages
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching messages',
        'error' => $e->getMessage()
    ]);
} 