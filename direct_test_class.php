<?php
// Direct test of class details retrieval with detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to plain text for better readability
header('Content-Type: text/plain');

// Include database connection
require_once 'db_connect.php';

// Get class ID from GET parameter
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 1;  // Default to class ID 1 if not provided

echo "==== TESTING CLASS ID: $class_id ====\n\n";

// Simulate teacher login
session_start();
$_SESSION['teacher_id'] = 8; // Use the correct teacher ID

// Query the class directly
try {
    // First check if class exists
    $check_query = "SELECT id, class_name FROM classes WHERE id = ?";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute([$class_id]);
    $class_exists = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class_exists) {
        echo "ERROR: Class with ID $class_id does not exist in the database.\n";
        exit;
    }
    
    echo "Class found: {$class_exists['class_name']} (ID: {$class_exists['id']})\n\n";
    
    // Now get full class details
    $query = "SELECT c.*, t.full_name AS teacher_name, 
              (SELECT COUNT(*) FROM class_students cs WHERE cs.class_id = c.id AND cs.status = 'active') AS student_count
              FROM classes c
              JOIN teachers t ON c.teacher_id = t.id
              WHERE c.id = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        echo "ERROR: Failed to retrieve detailed class info.\n";
        exit;
    }
    
    echo "==== RAW CLASS DATA ====\n";
    var_export($class);
    echo "\n\n";
    
    // Get students in this class
    $students_query = "SELECT s.id, s.full_name AS name, s.email, s.student_id 
                      FROM class_students cs 
                      JOIN students s ON cs.student_id = s.id 
                      WHERE cs.class_id = ? AND cs.status = 'active'
                      ORDER BY s.full_name";
    $students_stmt = $pdo->prepare($students_query);
    $students_stmt->execute([$class_id]);
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the expected output like get_class_details.php
    $response = [
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
    
    echo "==== FORMATTED RESPONSE ====\n";
    var_export($response);
    echo "\n\n";
    
    // Check for missing fields
    echo "==== FIELD VALIDATION ====\n";
    $requiredFields = ['id', 'class_name', 'class_code', 'created_at', 'teacher_id'];
    $missing = false;
    
    foreach ($requiredFields as $field) {
        if (!isset($class[$field]) || $class[$field] === null || $class[$field] === '') {
            echo "MISSING FIELD: $field\n";
            $missing = true;
        }
    }
    
    if (!$missing) {
        echo "All required fields are present\n";
    }
    
    // Test JSON encoding
    echo "\n==== JSON ENCODING TEST ====\n";
    $json = json_encode($response);
    
    if ($json === false) {
        echo "JSON ENCODING ERROR: " . json_last_error_msg() . "\n";
    } else {
        echo "JSON encoding successful\n";
        echo "JSON length: " . strlen($json) . " bytes\n";
    }
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
?> 