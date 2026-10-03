<?php
session_start();

// Check if user is logged in as teacher
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get the lesson ID from the request
$data = json_decode(file_get_contents('php://input'), true);
$lesson_id = isset($data['lesson_id']) ? intval($data['lesson_id']) : 0;

if (!$lesson_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid lesson ID']);
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'studyhub');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Verify that the lesson belongs to a class owned by the teacher
$verify_sql = "SELECT l.id FROM lessons l 
               JOIN classes c ON l.class_id = c.id 
               WHERE l.id = ? AND c.teacher_id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("ii", $lesson_id, $_SESSION['teacher_id']);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Lesson not found or unauthorized']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get file paths before deleting
    $files_sql = "SELECT file_path FROM lesson_files WHERE lesson_id = ?";
    $files_stmt = $conn->prepare($files_sql);
    $files_stmt->bind_param("i", $lesson_id);
    $files_stmt->execute();
    $files_result = $files_stmt->get_result();
    
    // Delete lesson (this will cascade delete files due to foreign key)
    $delete_sql = "DELETE FROM lessons WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $lesson_id);
    $delete_stmt->execute();
    
    // Delete physical files
    while ($file = $files_result->fetch_assoc()) {
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
    }
    
    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Lesson deleted successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error deleting lesson: ' . $e->getMessage()]);
}

// Close connections
$verify_stmt->close();
$conn->close();
?> 