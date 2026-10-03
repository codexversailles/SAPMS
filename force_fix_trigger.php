<?php
require_once 'db_connect.php';

header('Content-Type: text/html');
echo "<h1>Assessment Trigger Force Fix</h1>";

try {
    // Force drop any existing trigger
    $pdo->exec("DROP TRIGGER IF EXISTS after_assessment_insert");
    echo "<p>Force dropped any existing assessment trigger.</p>";
    
    // Create the assessment trigger
    $trigger_sql = "
    CREATE TRIGGER after_assessment_insert AFTER INSERT ON assessments FOR EACH ROW
    BEGIN
        DECLARE done INT DEFAULT FALSE;
        DECLARE student_id INT;
        DECLARE cur CURSOR FOR 
            SELECT student_id 
            FROM class_students 
            WHERE class_id = NEW.class_id AND student_id IS NOT NULL;
        DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
        
        OPEN cur;
        read_loop: LOOP
            FETCH cur INTO student_id;
            IF done THEN
                LEAVE read_loop;
            END IF;
            
            -- Only insert if student_id is not null
            IF student_id IS NOT NULL THEN
                INSERT INTO notifications (student_id, type, title, message, reference_id, class_id)
                VALUES (
                    student_id,
                    'assessment',
                    'New Assessment Added',
                    CONCAT('A new assessment \"', NEW.title, '\" has been added to your class'),
                    NEW.id,
                    NEW.class_id
                );
            END IF;
        END LOOP;
        CLOSE cur;
    END;
    ";
    
    $pdo->exec($trigger_sql);
    echo "<p style='color:green; font-weight:bold;'>Successfully created the assessment notification trigger!</p>";
    
    // Verify the trigger exists
    $stmt = $pdo->query("SHOW TRIGGERS LIKE 'after_assessment_insert'");
    $trigger = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Verification - Trigger details:</h2>";
    echo "<pre>";
    print_r($trigger);
    echo "</pre>";
    
    // Test the trigger with a sample assessment
    echo "<h2>Test the trigger</h2>";
    echo "<p>Click to <a href='?test=1'>create a sample assessment</a> to test the trigger</p>";
    
    if (isset($_GET['test'])) {
        // Get list of classes for testing
        $stmt = $pdo->query("SELECT id, class_name FROM classes LIMIT 5");
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Available classes for testing:</h3>";
        echo "<ul>";
        foreach ($classes as $class) {
            echo "<li>Class ID: {$class['id']} - {$class['class_name']}</li>";
        }
        echo "</ul>";
        
        // Get a valid class with students
        $stmt = $pdo->query("
            SELECT c.id, c.class_name, COUNT(cs.id) as student_count 
            FROM classes c
            JOIN class_students cs ON c.id = cs.class_id
            GROUP BY c.id
            HAVING student_count > 0
            LIMIT 1
        ");
        $test_class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test_class) {
            echo "<p>Using class ID: {$test_class['id']} ({$test_class['class_name']}) with {$test_class['student_count']} students</p>";
            
            // Insert a test assessment
            $stmt = $pdo->prepare("INSERT INTO assessments (class_id, title, description, due_date, max_score) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $test_class['id'],
                'Test Assessment ' . date('Y-m-d H:i:s'),
                'This is a test assessment to verify the trigger',
                date('Y-m-d H:i:s', strtotime('+1 week')),
                100
            ]);
            
            $assessment_id = $pdo->lastInsertId();
            echo "<p>Created test assessment with ID: $assessment_id</p>";
            
            // Check if notifications were created
            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE reference_id = ? AND type = 'assessment'");
            $stmt->execute([$assessment_id]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>Notifications created by trigger:</p>";
            echo "<pre>";
            print_r($notifications);
            echo "</pre>";
            
            if (count($notifications) > 0) {
                echo "<p style='color:green; font-weight:bold;'>✓ SUCCESS! The trigger is now working correctly.</p>";
            } else {
                echo "<p style='color:red; font-weight:bold;'>✗ ERROR: No notifications were created.</p>";
            }
        } else {
            echo "<p style='color:red;'>Could not find a class with students for testing. Please create a class with students first.</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p style='color:red;'>" . $e->getMessage() . "</p>";
    echo "<pre>";
    print_r($e->getTraceAsString());
    echo "</pre>";
}
?> 