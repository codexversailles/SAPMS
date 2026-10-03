<?php
// Set content type to plain text for better readability
header('Content-Type: text/plain');

// Include database connection
require_once 'db_connect.php';

// Get class ID from GET parameter
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 1;  // Default to class ID 1 if not provided

echo "==== CHECKING CLASS ID: $class_id ====\n\n";

try {
    // Direct class query
    $query = "SELECT c.*, t.full_name AS teacher_name, 
              (SELECT COUNT(*) FROM class_students cs WHERE cs.class_id = c.id AND cs.status = 'active') AS student_count
              FROM classes c
              JOIN teachers t ON c.teacher_id = t.id
              WHERE c.id = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        echo "ERROR: Class not found with ID $class_id\n";
        exit;
    }
    
    echo "CLASS RECORD:\n";
    foreach ($class as $key => $value) {
        echo "$key: " . ($value === null ? 'NULL' : $value) . "\n";
    }
    
    // Check for potential issues
    echo "\nVALIDATION:\n";
    
    $requiredFields = ['id', 'class_name', 'class_code', 'created_at', 'teacher_id'];
    $allFieldsPresent = true;
    
    foreach ($requiredFields as $field) {
        if (!isset($class[$field]) || $class[$field] === null || $class[$field] === '') {
            echo "MISSING: $field field is empty or null\n";
            $allFieldsPresent = false;
        }
    }
    
    if ($allFieldsPresent) {
        echo "All required fields are present\n";
    }
    
    // Get students in this class
    $students_query = "SELECT s.id, s.full_name, s.email, s.student_id 
                      FROM class_students cs 
                      JOIN students s ON cs.student_id = s.id 
                      WHERE cs.class_id = ? AND cs.status = 'active'
                      ORDER BY s.full_name";
    $students_stmt = $pdo->prepare($students_query);
    $students_stmt->execute([$class_id]);
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nSTUDENTS ENROLLED: " . count($students) . "\n";
    if (count($students) > 0) {
        foreach ($students as $i => $student) {
            echo "\nStudent " . ($i+1) . ":\n";
            foreach ($student as $key => $value) {
                echo "  $key: " . ($value === null ? 'NULL' : $value) . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
?> 