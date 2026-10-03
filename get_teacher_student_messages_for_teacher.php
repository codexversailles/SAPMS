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

// Check if required parameters are provided
if (!isset($_GET['student_id']) || !isset($_GET['class_id'])) {
    echo json_encode(['success' => false, 'message' => 'Student ID and Class ID are required']);
    exit;
}

$teacherId = $_SESSION['teacher_id'];
$studentId = $_GET['student_id'];
$classId = $_GET['class_id'];

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
    
    // Get teacher-student messages
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.message,
            m.created_at,
            m.sender_type,
            m.is_read,
            CASE 
                WHEN m.sender_type = 'student' THEN s.full_name
                WHEN m.sender_type = 'teacher' THEN t.full_name
                ELSE 'Unknown User'
            END as sender_name
        FROM teacher_student_messages m
        LEFT JOIN students s ON m.sender_type = 'student' AND m.student_id = s.id
        LEFT JOIN teachers t ON m.sender_type = 'teacher' AND m.teacher_id = t.id
        WHERE m.class_id = ? AND m.teacher_id = ? AND m.student_id = ?
        ORDER BY m.created_at ASC
        LIMIT 100
    ");
    
    $stmt->execute([$classId, $teacherId, $studentId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format messages with additional attributes for display
    $formattedMessages = [];
    foreach ($messages as $message) {
        $formattedMessage = $message;
        $formattedMessage['is_own'] = ($message['sender_type'] == 'teacher');
        $formattedMessage['formatted_time'] = date('h:i A', strtotime($message['created_at']));
        $formattedMessage['formatted_date'] = date('M j, Y', strtotime($message['created_at']));
        $formattedMessages[] = $formattedMessage;
    }
    
    // Mark all messages from student as read
    if (!empty($formattedMessages)) {
        $stmt = $pdo->prepare("
            UPDATE teacher_student_messages
            SET is_read = TRUE
            WHERE class_id = ? AND teacher_id = ? AND student_id = ? 
            AND sender_type = 'student' AND is_read = FALSE
        ");
        $stmt->execute([$classId, $teacherId, $studentId]);
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