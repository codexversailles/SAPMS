<?php
session_start();
header('Content-Type: application/json');

// Include database configuration
require_once 'config/database.php';

// Check if teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required. Please log in.']);
    exit;
}
$teacher_id = $_SESSION['teacher_id'];

// Get data from POST request
$class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : null;
$title = isset($_POST['title']) ? trim($_POST['title']) : null;
$content = isset($_POST['content']) ? trim($_POST['content']) : null;
$visibility = isset($_POST['visibility']) ? $_POST['visibility'] : 'class'; // Default to class
$priority = isset($_POST['priority']) ? $_POST['priority'] : 'normal'; // Default to normal

// Basic validation
if (empty($class_id) || empty($title) || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Class ID, title, and content are required.']);
    exit;
}

if (!in_array($visibility, ['class', 'parents'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid visibility option.']);
    exit;
}

if (!in_array($priority, ['normal', 'important', 'urgent'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid priority option.']);
    exit;
}

// Database connection (from database.php)
global $conn;

// Start transaction
$conn->begin_transaction();

try {
    // Insert announcement
    $stmt = $conn->prepare("INSERT INTO announcements (class_id, teacher_id, title, content, visibility, priority) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    $stmt->bind_param("iissss", $class_id, $teacher_id, $title, $content, $visibility, $priority);
    
    if (!$stmt->execute()) {
        throw new Exception("Error posting announcement: " . $stmt->error);
    }
    $announcement_id = $stmt->insert_id;
    $stmt->close();

    // Handle file uploads
    $uploaded_files_info = [];
    if (isset($_FILES['announcementFiles'])) {
        $upload_dir = 'uploads/announcements/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception('Failed to create upload directory.');
            }
        }

        $files = $_FILES['announcementFiles'];
        $file_count = count($files['name']);

        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file_tmp_name = $files['tmp_name'][$i];
                $file_name = basename($files['name'][$i]);
                $file_size = $files['size'][$i];
                $file_type = $files['type'][$i];

                // Sanitize filename (optional, but good practice)
                $safe_file_name = preg_replace("/[^a-zA-Z0-9._-]/", "_", $file_name);
                $unique_file_name = time() . '_' . uniqid() . '_' . $safe_file_name;
                $destination = $upload_dir . $unique_file_name;

                if (move_uploaded_file($file_tmp_name, $destination)) {
                    // Insert file info into announcement_files table
                    $stmt_file = $conn->prepare("INSERT INTO announcement_files (announcement_id, file_name, file_path, file_size, file_type) VALUES (?, ?, ?, ?, ?)");
                    if (!$stmt_file) {
                        throw new Exception("Prepare statement for file failed: " . $conn->error);
                    }
                    $stmt_file->bind_param("issis", $announcement_id, $file_name, $destination, $file_size, $file_type);
                    if (!$stmt_file->execute()) {
                        // If file insert fails, attempt to delete the uploaded file to prevent orphans
                        if (file_exists($destination)) {
                            unlink($destination);
                        }
                        throw new Exception("Error saving file information: " . $stmt_file->error);
                    }
                    $stmt_file->close();
                    $uploaded_files_info[] = ['name' => $file_name, 'path' => $destination];
                } else {
                    // Log error or handle more gracefully
                    error_log("Failed to move uploaded file: " . $file_name);
                    // Decide if this should be a fatal error for the whole transaction
                    // For now, we continue but you might want to throw new Exception here
                }
            } elseif ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                // Handle other upload errors if necessary
                error_log("File upload error for " . $files['name'][$i] . ": " . $files['error'][$i]);
            }
        }
    }

    // Commit transaction
    $conn->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Announcement posted successfully!',
        'announcement_id' => $announcement_id,
        'files_uploaded' => $uploaded_files_info
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    // Log the detailed error for the admin/developer
    error_log("Announcement post error: " . $e->getMessage() . " - Data: " . print_r($_POST, true) . " - Files: " . print_r($_FILES, true));
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

// closeDbConnection(); // Connection is closed by register_shutdown_function in database.php
?> 