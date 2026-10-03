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
    
    // Get all student messages with additional info
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.teacher_id,
            m.student_id,
            m.class_id,
            m.sender_type,
            m.message,
            m.is_read,
            m.created_at,
            s.full_name AS student_name,
            s.email AS student_email,
            c.class_name
        FROM teacher_student_messages m
        JOIN students s ON m.student_id = s.id
        JOIN classes c ON m.class_id = c.id
        WHERE m.teacher_id = :teacher_id
        ORDER BY m.created_at DESC
        LIMIT 100
    ");
    
    $stmt->execute(['teacher_id' => $teacherId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert timestamps to readable format
    foreach ($messages as &$message) {
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