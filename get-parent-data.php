<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['parent_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in'
    ]);
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'studyhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get parent data
    $stmt = $pdo->prepare("SELECT id, parent_full_name as full_name, email, student_id, student_full_name, relationship FROM parents WHERE id = ?");
    $stmt->execute([$_SESSION['parent_id']]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($parent) {
        // In the future, we'll add:
        // 1. Children list
        // 2. Recent grades
        // 3. Attendance records
        // 4. Messages
        
        echo json_encode([
            'success' => true,
            'parent' => $parent,
            'children' => [], // To be implemented
            'grades' => [], // To be implemented
            'attendance' => [], // To be implemented
            'messages' => [] // To be implemented
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Parent not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 