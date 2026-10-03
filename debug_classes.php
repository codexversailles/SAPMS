<?php
// Set content type to plain text for better readability
header('Content-Type: text/plain');

// Include database connection
require_once 'db_connect.php';

echo "==== CLASSES TABLE DEBUG ====\n\n";

try {
    // Query all classes
    $stmt = $pdo->query('SELECT * FROM classes');
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total classes found: " . count($classes) . "\n\n";
    
    // Output each class
    foreach ($classes as $index => $class) {
        echo "==== CLASS " . ($index + 1) . " ====\n";
        foreach ($class as $key => $value) {
            echo "$key: " . ($value === null ? 'NULL' : $value) . "\n";
        }
        
        // Count students in this class
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM class_students WHERE class_id = ? AND status = "active"');
        $stmt->execute([$class['id']]);
        $studentCount = $stmt->fetchColumn();
        
        echo "student_count: $studentCount\n";
        echo "\n";
    }
    
    // Check for possible missing data
    echo "==== CHECKING FOR POTENTIAL ISSUES ====\n";
    foreach ($classes as $class) {
        if (empty($class['class_code'])) {
            echo "WARNING: Class ID {$class['id']} ({$class['class_name']}) has empty class_code\n";
        }
        
        if (empty($class['created_at'])) {
            echo "WARNING: Class ID {$class['id']} ({$class['class_name']}) has empty created_at\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
?> 