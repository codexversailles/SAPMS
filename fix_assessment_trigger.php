<?php
require_once 'db_connect.php';

header('Content-Type: text/html');
echo "<h1>Assessment Trigger Fix</h1>";

try {
    // Check if trigger exists
    $stmt = $pdo->query("SHOW TRIGGERS LIKE 'after_assessment_insert'");
    $trigger = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($trigger) {
        echo "<p>The assessment notification trigger already exists. Details:</p>";
        echo "<pre>";
        print_r($trigger);
        echo "</pre>";
        
        echo "<h2>Drop and recreate? <a href='?recreate=1'>Click here</a></h2>";
        
        if (isset($_GET['recreate'])) {
            $pdo->exec("DROP TRIGGER IF EXISTS after_assessment_insert");
            echo "<p>Existing trigger dropped.</p>";
        } else {
            exit;
        }
    }
    
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
    
    // Test the trigger with a sample assessment
    if (isset($_GET['test'])) {
        // Insert a test assessment
        $stmt = $pdo->prepare("INSERT INTO assessments (class_id, title, description, due_date, max_score) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            3, // Use an existing class
            'Test Assessment',
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
    } else {
        echo "<p>Would you like to test the trigger with a sample assessment? <a href='?test=1'>Click here</a></p>";
    }
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p style='color:red;'>" . $e->getMessage() . "</p>";
    echo "<pre>";
    print_r($e->getTraceAsString());
    echo "</pre>";
}
?> 