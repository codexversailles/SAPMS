<?php
require_once 'db_connect.php';

header('Content-Type: text/html');
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

echo "<h1>Assessment Notification System Debug</h1>";

try {
    echo "<div class='section'>";
    echo "<h2>1. Examining Database Structure</h2>";
    
    // Check assessments table
    $stmt = $pdo->query("DESCRIBE assessments");
    $assessments_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Assessments Table Structure:</h3>";
    echo "<pre>";
    print_r($assessments_structure);
    echo "</pre>";
    
    // Check notifications table
    $stmt = $pdo->query("DESCRIBE notifications");
    $notifications_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Notifications Table Structure:</h3>";
    echo "<pre>";
    print_r($notifications_structure);
    echo "</pre>";
    
    // Check class_students table
    $stmt = $pdo->query("DESCRIBE class_students");
    $class_students_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Class_Students Table Structure:</h3>";
    echo "<pre>";
    print_r($class_students_structure);
    echo "</pre>";
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>2. Test Manual Insert to Notifications Table</h2>";
    // Test direct insert into notifications
    $test_class_id = 2; // Filipino class from your output
    
    // Get students in this class
    $stmt = $pdo->prepare("SELECT student_id FROM class_students WHERE class_id = ? AND status = 'active'");
    $stmt->execute([$test_class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Students in Class ID $test_class_id:</h3>";
    echo "<pre>";
    print_r($students);
    echo "</pre>";
    
    if (count($students) > 0) {
        // Get first student
        $test_student_id = $students[0]['student_id'];
        
        // Try direct insert
        $stmt = $pdo->prepare("
            INSERT INTO notifications (student_id, type, title, message, reference_id, class_id)
            VALUES (?, 'assessment', 'Manual Test Notification', 'This is a manual test', 999, ?)
        ");
        $stmt->execute([$test_student_id, $test_class_id]);
        
        $manual_notification_id = $pdo->lastInsertId();
        if ($manual_notification_id) {
            echo "<p class='success'>✓ Successfully inserted manual notification with ID: $manual_notification_id</p>";
            
            // Get the notification
            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ?");
            $stmt->execute([$manual_notification_id]);
            $manual_notification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<pre>";
            print_r($manual_notification);
            echo "</pre>";
        } else {
            echo "<p class='error'>✗ Failed to insert manual notification.</p>";
        }
    } else {
        echo "<p class='error'>✗ No students found in this class.</p>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>3. Recreate Trigger With Direct SQL</h2>";
    // Drop and recreate trigger with direct SQL rather than PDO
    $pdo->exec("DROP TRIGGER IF EXISTS after_assessment_insert");
    
    $new_trigger_sql = "
    CREATE TRIGGER after_assessment_insert 
    AFTER INSERT ON assessments 
    FOR EACH ROW 
    BEGIN
        INSERT INTO notifications (student_id, type, title, message, reference_id, class_id)
        SELECT 
            cs.student_id,
            'assessment',
            'New Assessment Added',
            CONCAT('A new assessment \"', NEW.title, '\" has been added to your class'),
            NEW.id,
            NEW.class_id
        FROM 
            class_students cs
        WHERE 
            cs.class_id = NEW.class_id 
            AND cs.status = 'active'
            AND cs.student_id IS NOT NULL;
    END;
    ";
    
    $pdo->exec($new_trigger_sql);
    echo "<p class='success'>✓ Recreated trigger with simpler direct SQL approach</p>";
    
    // Verify the trigger
    $stmt = $pdo->query("SHOW TRIGGERS LIKE 'after_assessment_insert'");
    $trigger = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>New Trigger Details:</h3>";
    echo "<pre>";
    print_r($trigger);
    echo "</pre>";
    
    // Test the new trigger
    echo "<h3>Test New Trigger:</h3>";
    if (isset($_GET['test_new'])) {
        // Insert test assessment
        $stmt = $pdo->prepare("
            INSERT INTO assessments (class_id, title, description, due_date, max_score) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $test_class_id,
            'Test Assessment ' . date('Y-m-d H:i:s'),
            'Testing simplified trigger',
            date('Y-m-d H:i:s', strtotime('+1 week')),
            100
        ]);
        
        $new_assessment_id = $pdo->lastInsertId();
        echo "<p>Created test assessment with ID: $new_assessment_id</p>";
        
        // Check notifications
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE reference_id = ? AND type = 'assessment'");
        $stmt->execute([$new_assessment_id]);
        $trigger_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Notifications created by new trigger:</p>";
        echo "<pre>";
        print_r($trigger_notifications);
        echo "</pre>";
        
        if (count($trigger_notifications) > 0) {
            echo "<p class='success'>✓ SUCCESS! The new trigger is working correctly.</p>";
        } else {
            echo "<p class='error'>✗ ERROR: Still no notifications created.</p>";
            
            // Additional debugging - check directly for any recent notifications
            $stmt = $pdo->query("SELECT * FROM notifications ORDER BY id DESC LIMIT 5");
            $recent_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>5 Most Recent Notifications:</h4>";
            echo "<pre>";
            print_r($recent_notifications);
            echo "</pre>";
        }
    } else {
        echo "<p><a href='?test_new=1'>Click here to test the new trigger</a></p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 class='error'>Error:</h2>";
    echo "<p class='error'>" . $e->getMessage() . "</p>";
    echo "<pre>";
    print_r($e->getTraceAsString());
    echo "</pre>";
}
?> 