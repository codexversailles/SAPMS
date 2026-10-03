<?php
session_start();
header('Content-Type: application/json');

// Include database configuration
require_once 'config/database.php';

// Check if student is logged in
$student_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : null;
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;

// Check for required parameters
if (empty($class_id)) {
    echo json_encode(['success' => false, 'message' => 'Class ID is required.']);
    exit;
}

// Verify student has access to this class (if student_id is provided)
if ($student_id) {
    $stmt = $conn->prepare("SELECT id FROM class_students WHERE student_id = ? AND class_id = ? AND status = 'active'");
    $stmt->bind_param('ii', $student_id, $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'You do not have access to this class.']);
        exit;
    }
    $stmt->close();
}

// Get announcements for this class (including those visible to both class and parents)
$stmt = $conn->prepare("
    SELECT a.*, t.full_name as teacher_name
    FROM announcements a
    JOIN teachers t ON a.teacher_id = t.id
    WHERE a.class_id = ?
    ORDER BY a.created_at DESC
");
$stmt->bind_param('i', $class_id);
$stmt->execute();
$result = $stmt->get_result();

$announcements = [];
while ($row = $result->fetch_assoc()) {
    $announcement = [
        'id' => $row['id'],
        'title' => $row['title'],
        'content' => $row['content'],
        'teacher_name' => $row['teacher_name'],
        'created_at' => $row['created_at'],
        'visibility' => $row['visibility'],
        'priority' => $row['priority'],
        'files' => []
    ];
    
    // Get files for this announcement if any
    $stmt_files = $conn->prepare("
        SELECT id, file_name, file_path, file_size, file_type
        FROM announcement_files
        WHERE announcement_id = ?
    ");
    $stmt_files->bind_param('i', $row['id']);
    $stmt_files->execute();
    $result_files = $stmt_files->get_result();
    
    while ($file = $result_files->fetch_assoc()) {
        $announcement['files'][] = [
            'id' => $file['id'],
            'file_name' => $file['file_name'],
            'file_path' => $file['file_path'],
            'file_size' => $file['file_size'],
            'file_type' => $file['file_type']
        ];
    }
    
    $stmt_files->close();
    $announcements[] = $announcement;
}

$stmt->close();

// Return the announcements as JSON
echo json_encode([
    'success' => true,
    'announcements' => $announcements,
    'count' => count($announcements)
]); 