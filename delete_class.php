<?php
// Start session to access teacher's information
session_start();

// Check if user is logged in as teacher
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

// Validate the input
if (!isset($data['class_id']) || !is_numeric($data['class_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
    exit;
}

$class_id = $data['class_id'];
$teacher_id = $_SESSION['teacher_id'];

// Database connection
$conn = new mysqli('localhost', 'root', '', 'studyhub');

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// First verify that the class belongs to the teacher
$verify_sql = "SELECT id FROM classes WHERE id = ? AND teacher_id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("ii", $class_id, $teacher_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Class not found or unauthorized']);
    exit;
}

// Delete the class
$delete_sql = "DELETE FROM classes WHERE id = ? AND teacher_id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("ii", $class_id, $teacher_id);

if ($delete_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Class deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting class']);
}

// Close connections
$delete_stmt->close();
$verify_stmt->close();
$conn->close();
?> 