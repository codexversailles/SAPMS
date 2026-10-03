<?php
session_start();
require_once 'config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in as teacher']);
    exit;
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$postData = $_POST;
if (empty($postData) && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $postData = json_decode(file_get_contents('php://input'), true);
}

// Check if required fields are provided
if (!isset($postData['student_id']) || empty($postData['student_id']) || 
    !isset($postData['class_id']) || empty($postData['class_id']) ||
    !isset($postData['message']) || empty($postData['message'])) {
    echo json_encode(['success' => false, 'message' => 'Student ID, Class ID, and message are required']);
    exit;
}

$studentId = $postData['student_id'];
$classId = $postData['class_id'];
$message = trim($postData['message']);
$teacherId = $_SESSION['teacher_id'];

// Validate message length
if (strlen($message) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Message is too long (maximum 1000 characters)']);
    exit;
}

try {
    // First verify that the teacher teaches this class
    $stmt = $pdo->prepare("
        SELECT 1 FROM classes 
        WHERE id = ? AND teacher_id = ?
    ");
    $stmt->execute([$classId, $teacherId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'You do not teach this class']);
        exit;
    }
    
    // Verify that the student is enrolled in this class
    $stmt = $pdo->prepare("
        SELECT 1 FROM class_students 
        WHERE class_id = ? AND student_id = ? AND status = 'active'
    ");
    $stmt->execute([$classId, $studentId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not enrolled in this class']);
        exit;
    }
    
    // Insert the message
    $stmt = $pdo->prepare("
        INSERT INTO teacher_student_messages (teacher_id, student_id, class_id, sender_type, message)
        VALUES (?, ?, ?, 'teacher', ?)
    ");
    
    $stmt->execute([$teacherId, $studentId, $classId, $message]);
    $messageId = $pdo->lastInsertId();
    
    // Get sender name
    $stmt = $pdo->prepare("
        SELECT full_name FROM teachers WHERE id = ?
    ");
    $stmt->execute([$teacherId]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    $senderName = $teacher ? $teacher['full_name'] : 'Unknown Teacher';
    
    // Create formatted message for response
    $now = new DateTime();
    $newMessage = [
        'id' => $messageId,
        'message' => $message,
        'created_at' => $now->format('Y-m-d H:i:s'),
        'sender_type' => 'teacher',
        'is_read' => false,
        'sender_name' => $senderName,
        'is_own' => true,
        'formatted_time' => $now->format('h:i A'),
        'formatted_date' => $now->format('M j, Y')
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully',
        'data' => $newMessage
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while sending the message',
        'error' => $e->getMessage()
    ]);
} 