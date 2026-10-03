<?php
session_start();
header('Content-Type: application/json');

// Check if announcement_id parameter is provided
if (!isset($_GET['announcement_id']) || empty($_GET['announcement_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Announcement ID is required'
    ]);
    exit;
}

$announcementId = (int)$_GET['announcement_id'];

// Database connection
$host = 'localhost';
$dbname = 'studyhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

try {
    // Check if the user is either a parent or a student
    $isParentLoggedIn = isset($_SESSION['parent_id']);
    $isStudentLoggedIn = isset($_SESSION['student_id']);
    
    if (!$isParentLoggedIn && !$isStudentLoggedIn) {
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit;
    }
    
    // Verify the announcement exists and get its visibility
    $stmt = $pdo->prepare("
        SELECT a.visibility, a.class_id
        FROM announcements a
        WHERE a.id = ?
    ");
    $stmt->execute([$announcementId]);
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$announcement) {
        echo json_encode([
            'success' => false,
            'message' => 'Announcement not found'
        ]);
        exit;
    }
    
    // Get files for this announcement
    $stmt = $pdo->prepare("
        SELECT id, file_name, file_path, file_size, file_type, uploaded_at
        FROM announcement_files
        WHERE announcement_id = ?
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([$announcementId]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the files data
    echo json_encode([
        'success' => true,
        'files' => $files
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching files: ' . $e->getMessage()
    ]);
}
?> 