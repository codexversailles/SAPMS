<?php
session_start();

// Check if user is logged in as teacher
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get the class ID from the modal
$class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
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

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/lessons/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert lesson details
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    $lesson_sql = "INSERT INTO lessons (class_id, title, description) VALUES (?, ?, ?)";
    $lesson_stmt = $conn->prepare($lesson_sql);
    $lesson_stmt->bind_param("iss", $class_id, $title, $description);
    $lesson_stmt->execute();
    
    $lesson_id = $conn->insert_id;
    
    // Handle file uploads
    if (isset($_FILES['files'])) {
        $files = $_FILES['files'];
        $file_count = count($files['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file_name = $files['name'][$i];
                $file_tmp = $files['tmp_name'][$i];
                $file_size = $files['size'][$i];
                $file_type = $files['type'][$i];
                
                // Generate unique filename
                $unique_name = uniqid() . '_' . $file_name;
                $file_path = $upload_dir . $unique_name;
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $file_path)) {
                    // Insert file information into database
                    $file_sql = "INSERT INTO lesson_files (lesson_id, file_name, file_path, file_type, file_size) 
                                VALUES (?, ?, ?, ?, ?)";
                    $file_stmt = $conn->prepare($file_sql);
                    $file_stmt->bind_param("isssi", $lesson_id, $file_name, $file_path, $file_type, $file_size);
                    $file_stmt->execute();
                }
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Lesson created successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error creating lesson: ' . $e->getMessage()]);
}

// Close connections
$verify_stmt->close();
$conn->close();
?> 