<?php
require_once 'db_connect.php';

header('Content-Type: text/html');
echo "<h1>Database Trigger Check</h1>";

try {
    // Check assessment trigger
    $stmt = $pdo->query("SHOW TRIGGERS LIKE 'after_assessment_insert'");
    $trigger = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Assessment Trigger:</h2>";
    echo "<pre>";
    print_r($trigger);
    echo "</pre>";
    
    if (!$trigger) {
        echo "<p style='color:red'>The after_assessment_insert trigger is missing!</p>";
    }
    
    // Check lesson trigger for comparison
    $stmt = $pdo->query("SHOW TRIGGERS LIKE 'after_lesson_insert'");
    $lesson_trigger = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Lesson Trigger (for comparison):</h2>";
    echo "<pre>";
    print_r($lesson_trigger);
    echo "</pre>";
    
    // Check if any assessment notifications exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications WHERE type = 'assessment'");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<h2>Assessment Notifications Count: $count</h2>";
    
    // Check sample notifications
    $stmt = $pdo->query("SELECT * FROM notifications LIMIT 10");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Sample Notifications:</h2>";
    echo "<pre>";
    print_r($notifications);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 