<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
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
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
    exit;
}

// Get student data
$studentId = $_SESSION['student_id'];

try {
    // Get student information
    $stmt = $pdo->prepare("SELECT id, student_id, full_name, email FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    
    // In the future, we'll fetch:
    // 1. Upcoming lessons
    // 2. Recent activities
    // 3. Grades
    // 4. Recent messages
    
    // For now, return placeholder data
    $response = [
        'success' => true,
        'student' => [
            'id' => $student['id'],
            'student_id' => $student['student_id'],
            'full_name' => $student['full_name'],
            'email' => $student['email']
        ],
        'upcoming_lessons' => [],
        'recent_activities' => [],
        'grades' => [],
        'recent_messages' => []
    ];
    
    echo json_encode($response);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching data: ' . $e->getMessage()]);
}
?> 