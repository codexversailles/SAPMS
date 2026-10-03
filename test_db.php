<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

// Include database connection
require_once 'config.php';

// Test database connection
echo "<h2>Connection Test</h2>";
try {
    echo "Database connection: " . ($pdo ? "SUCCESS" : "FAILED") . "<br>";
    echo "PDO attributes: <pre>" . print_r($pdo->getAttributes(), true) . "</pre><br>";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// Test classes table
echo "<h2>Classes Table Test</h2>";
try {
    $query = "SELECT * FROM classes LIMIT 5";
    $stmt = $pdo->query($query);
    $results = $stmt->fetchAll();
    echo "Found " . count($results) . " classes<br>";
    echo "<pre>" . print_r($results, true) . "</pre>";
} catch (Exception $e) {
    echo "Classes query error: " . $e->getMessage() . "<br>";
}

// Test lessons table
echo "<h2>Lessons Table Test</h2>";
try {
    $query = "SELECT * FROM lessons LIMIT 5";
    $stmt = $pdo->query($query);
    $results = $stmt->fetchAll();
    echo "Found " . count($results) . " lessons<br>";
    echo "<pre>" . print_r($results, true) . "</pre>";
} catch (Exception $e) {
    echo "Lessons query error: " . $e->getMessage() . "<br>";
}

// Test assessments table
echo "<h2>Assessments Table Test</h2>";
try {
    $query = "SELECT * FROM assessments LIMIT 5";
    $stmt = $pdo->query($query);
    $results = $stmt->fetchAll();
    echo "Found " . count($results) . " assessments<br>";
    echo "<pre>" . print_r($results, true) . "</pre>";
} catch (Exception $e) {
    echo "Assessments query error: " . $e->getMessage() . "<br>";
}

// Test a specific class with its lessons and assessments
echo "<h2>Specific Class Test (ID: 4)</h2>";
try {
    $class_id = 4;
    
    // Get class details
    $query = "SELECT * FROM classes WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $class_id]);
    $class = $stmt->fetch();
    
    if ($class) {
        echo "Class found: " . $class['class_name'] . "<br>";
        
        // Get lessons
        $query = "SELECT * FROM lessons WHERE class_id = :class_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['class_id' => $class_id]);
        $lessons = $stmt->fetchAll();
        echo "Found " . count($lessons) . " lessons for this class<br>";
        if (count($lessons) > 0) {
            echo "<pre>" . print_r($lessons, true) . "</pre>";
        }
        
        // Get assessments
        $query = "SELECT * FROM assessments WHERE class_id = :class_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['class_id' => $class_id]);
        $assessments = $stmt->fetchAll();
        echo "Found " . count($assessments) . " assessments for this class<br>";
        if (count($assessments) > 0) {
            echo "<pre>" . print_r($assessments, true) . "</pre>";
        }
    } else {
        echo "Class with ID $class_id not found<br>";
    }
} catch (Exception $e) {
    echo "Specific class test error: " . $e->getMessage() . "<br>";
}
?> 