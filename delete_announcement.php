<?php
session_start();
require_once 'db_connect.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in as a teacher
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized. Please login as a teacher.']);
    exit;
}

$teacher_id = $_SESSION['teacher_id'];

// Check if announcement_id is provided
if (!isset($_POST['announcement_id'])) {
    echo json_encode(['success' => false, 'message' => 'Announcement ID is required.']);
    exit;
}

$announcement_id = $_POST['announcement_id'];

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // First, check if the announcement exists and belongs to this teacher
    $stmt = $pdo->prepare("
        SELECT a.*, c.teacher_id 
        FROM announcements a
        JOIN classes c ON a.class_id = c.id
        WHERE a.id = ? AND c.teacher_id = ?
    ");
    $stmt->execute([$announcement_id, $teacher_id]);
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$announcement) {
        // Announcement not found or does not belong to this teacher
        echo json_encode(['success' => false, 'message' => 'Announcement not found or you do not have permission to delete it.']);
        $pdo->rollBack();
        exit;
    }
    
    // Delete any files associated with this announcement
    $stmt = $pdo->prepare("
        DELETE FROM announcement_files 
        WHERE announcement_id = ?
    ");
    $stmt->execute([$announcement_id]);
    
    // Delete the announcement
    $stmt = $pdo->prepare("
        DELETE FROM announcements 
        WHERE id = ?
    ");
    $stmt->execute([$announcement_id]);
    
    // Commit transaction
    $pdo->commit();
    
    // Return success
    echo json_encode(['success' => true, 'message' => 'Announcement deleted successfully.']);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log("Error deleting announcement: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the announcement. Please try again.']);
}
?> 