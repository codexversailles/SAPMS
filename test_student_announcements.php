<?php
header('Content-Type: application/json');

// Get student ID from request
$studentId = isset($_GET['student_id']) ? $_GET['student_id'] : 'STU2025004'; // Default to a sample ID

// Database connection
$host = 'localhost';
$dbname = 'studyhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First, get the student's internal ID from the student_id
    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode([
            'success' => false,
            'message' => 'Student not found with ID: ' . $studentId
        ]);
        exit;
    }
    
    $internalStudentId = $student['id'];
    
    // Test 1: Get classes the student is enrolled in
    $stmt = $pdo->prepare("
        SELECT c.id, c.class_name
        FROM classes c
        JOIN class_students cs ON c.id = cs.class_id
        WHERE cs.student_id = ?
    ");
    $stmt->execute([$internalStudentId]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test 2: Get announcements for these classes
    $classIds = [];
    foreach ($classes as $class) {
        $classIds[] = $class['id'];
    }
    
    if (empty($classIds)) {
        echo json_encode([
            'success' => true,
            'message' => 'Student is not enrolled in any classes',
            'student_id' => $studentId,
            'internal_id' => $internalStudentId,
            'classes' => [],
            'announcements' => []
        ]);
        exit;
    }
    
    $placeholders = implode(',', array_fill(0, count($classIds), '?'));
    
    $stmt = $pdo->prepare("
        SELECT a.*, c.class_name, t.full_name as teacher_name
        FROM announcements a
        JOIN classes c ON a.class_id = c.id
        JOIN teachers t ON a.teacher_id = t.id
        WHERE a.class_id IN ($placeholders) AND (a.visibility = 'parents' OR a.visibility = 'class')
        ORDER BY a.created_at DESC
    ");
    $stmt->execute($classIds);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the test results
    echo json_encode([
        'success' => true,
        'student_id' => $studentId,
        'internal_id' => $internalStudentId,
        'classes' => $classes,
        'class_ids' => $classIds,
        'announcements_count' => count($announcements),
        'announcements' => $announcements
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 