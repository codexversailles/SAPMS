<?php
session_start();

// Check if user is logged in as teacher
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get class ID from request
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
if (!$class_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
    exit;
}

// Validate that the class belongs to the teacher
$conn = new mysqli('localhost', 'root', '', 'studyhub');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$verify_sql = "SELECT id FROM classes WHERE id = ? AND teacher_id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("ii", $class_id, $_SESSION['teacher_id']);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Class not found or unauthorized']);
    exit;
}

// Get lessons for the class
$lessons_sql = "SELECT id, title, description, created_at FROM lessons WHERE class_id = ? ORDER BY created_at DESC";
$lessons_stmt = $conn->prepare($lessons_sql);
$lessons_stmt->bind_param("i", $class_id);
$lessons_stmt->execute();
$lessons_result = $lessons_stmt->get_result();

$lessons = [];
while ($lesson = $lessons_result->fetch_assoc()) {
    // Get files for each lesson
    $files_sql = "SELECT id, file_name, file_path, file_type, file_size FROM lesson_files WHERE lesson_id = ?";
    $files_stmt = $conn->prepare($files_sql);
    $files_stmt->bind_param("i", $lesson['id']);
    $files_stmt->execute();
    $files_result = $files_stmt->get_result();
    
    $lesson['files'] = [];
    while ($file = $files_result->fetch_assoc()) {
        $lesson['files'][] = $file;
    }
    
    $lessons[] = $lesson;
}

echo json_encode(['success' => true, 'lessons' => $lessons]);

// Close connections
$verify_stmt->close();
$lessons_stmt->close();
$conn->close();
?> 