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

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$postData = json_decode(file_get_contents('php://input'), true);

// Check if required fields are provided
if (!isset($postData['class_id']) || empty($postData['class_id']) || 
    !isset($postData['message']) || empty($postData['message'])) {
    echo json_encode(['success' => false, 'message' => 'Class ID and message are required']);
    exit;
}

$classId = $postData['class_id'];
$message = trim($postData['message']);
$studentId = $_SESSION['student_id'];

// Validate message length
if (strlen($message) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Message is too long (maximum 1000 characters)']);
    exit;
}

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
    
    // Insert the message
    $stmt = $pdo->prepare("
        INSERT INTO class_chat_messages (class_id, sender_id, sender_type, message)
        VALUES (?, ?, 'student', ?)
    ");
    
    $stmt->execute([$classId, $studentId, $message]);
    $messageId = $pdo->lastInsertId();
    
    // Get the newly created message with sender info
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.message,
            m.created_at,
            m.sender_type,
            m.sender_id,
            s.full_name as sender_name
        FROM class_chat_messages m
        JOIN students s ON m.sender_id = s.id
        WHERE m.id = ? AND m.sender_type = 'student'
    ");
    
    $stmt->execute([$messageId]);
    $newMessage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($newMessage) {
        // Add formatted time for display
        $newMessage['is_own'] = true;
        $newMessage['formatted_time'] = date('h:i A', strtotime($newMessage['created_at']));
        $newMessage['formatted_date'] = date('M j, Y', strtotime($newMessage['created_at']));
        
        echo json_encode([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $newMessage
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to retrieve sent message'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while sending the message',
        'error' => $e->getMessage()
    ]);
} 