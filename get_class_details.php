<?php
// Start session to get user ID
session_start();
require_once 'db_connect.php';

// For debugging - uncomment if needed
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Debug current session
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is logged in (either as student or teacher)
// Prioritize teacher session if both are present
$isTeacher = isset($_SESSION['teacher_id']) && !empty($_SESSION['teacher_id']);
$isStudent = isset($_SESSION['student_id']) && !empty($_SESSION['student_id']) && !$isTeacher;

// Force teacher session for testing if needed
// $_SESSION['teacher_id'] = 8; // Uncomment for testing
// $isTeacher = true;
// $isStudent = false;

error_log("Is Teacher: " . ($isTeacher ? 'Yes' : 'No'));
error_log("Is Student: " . ($isStudent ? 'Yes' : 'No'));

if (!$isStudent && !$isTeacher) {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in'
    ]);
    exit;
}

// Get class ID from query parameter (support both 'class_id' and 'id' parameters)
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
if (!$class_id) {
    $class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
}

if (!$class_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Class ID is required'
    ]);
    exit;
}

try {
    // Only check enrollment if user is a student
    if ($isStudent && !$isTeacher) {
        // Check if the student is enrolled in this class
        $enrollment_query = "SELECT cs.id FROM class_students cs 
                            WHERE cs.class_id = ? AND cs.student_id = ? AND cs.status = 'active'";
        $enrollment_stmt = $pdo->prepare($enrollment_query);
        $enrollment_stmt->execute([$class_id, $_SESSION['student_id']]);
        
        if ($enrollment_stmt->rowCount() === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'You are not enrolled in this class'
            ]);
            exit;
        }
    }
    
    // For teachers, ensure they own the class
    if ($isTeacher) {
        $ownership_query = "SELECT id FROM classes WHERE id = ? AND teacher_id = ?";
        $ownership_stmt = $pdo->prepare($ownership_query);
        $ownership_stmt->execute([$class_id, $_SESSION['teacher_id']]);
        
        if ($ownership_stmt->rowCount() === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'You do not have permission to view this class'
            ]);
            exit;
        }
    }
    
    // Fetch class details including teacher name
    $query = "SELECT c.*, t.full_name AS teacher_name, 
              (SELECT COUNT(*) FROM class_students cs WHERE cs.class_id = c.id AND cs.status = 'active') AS student_count
              FROM classes c
              JOIN teachers t ON c.teacher_id = t.id
              WHERE c.id = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        echo json_encode([
            'success' => false,
            'message' => 'Class not found'
        ]);
        exit;
    }
    
    // Ensure all required fields have valid values
    if (empty($class['class_code'])) {
        $class['class_code'] = 'N/A';
    }
    
    if (empty($class['created_at'])) {
        $class['created_at'] = date('Y-m-d H:i:s');
    }
    
    if (!isset($class['student_count'])) {
        $class['student_count'] = 0;
    }
    
    // Fetch students in this class if teacher is requesting
    $students = [];
    if ($isTeacher) {
        $students_query = "SELECT s.id, s.full_name AS name, s.email, s.student_id 
                          FROM class_students cs 
                          JOIN students s ON cs.student_id = s.id 
                          WHERE cs.class_id = ? AND cs.status = 'active'
                          ORDER BY s.full_name";
        $students_stmt = $pdo->prepare($students_query);
        $students_stmt->execute([$class_id]);
        $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Return different format for teacher vs student
    if ($isTeacher) {
        $responseData = [
            'success' => true,
            'class' => [
                'id' => $class['id'],
                'class_name' => $class['class_name'],
                'class_code' => $class['class_code'],
                'teacher_id' => $class['teacher_id'],
                'teacher_name' => $class['teacher_name'],
                'created_at' => $class['created_at'],
                'student_count' => $class['student_count'],
                'students' => $students
            ]
        ];
        
        echo json_encode($responseData);
    } else {
        // Student view (more limited)
        echo json_encode([
            'success' => true,
            'class_id' => $class['id'],
            'class_name' => $class['class_name'],
            'teacher_id' => $class['teacher_id'],
            'teacher_name' => $class['teacher_name'],
            'class_code' => $class['class_code']
        ]);
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Class details error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching class details: ' . $e->getMessage()
    ]);
}
?> 