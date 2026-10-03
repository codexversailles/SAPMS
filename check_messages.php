<?php
// A simple script to check if the parent_teacher_messages table exists and has data
header('Content-Type: text/html');

echo '<h1>Parent-Teacher Messages Check</h1>';

try {
    // Include the database connection
    require_once 'db_connect.php';
    
    if (!isset($pdo)) {
        throw new Exception('Database connection variable $pdo is not defined');
    }
    
    // Check if the parent_teacher_messages table exists
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS table_exists 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'parent_teacher_messages'
    ");
    
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row['table_exists'] > 0) {
        echo '<p style="color: green;">✅ parent_teacher_messages table exists</p>';
        
        // Check the structure of the table
        $stmt = $pdo->query("DESCRIBE parent_teacher_messages");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<h2>Table Structure</h2>';
        echo '<table border="1" cellpadding="5">';
        echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
        
        foreach ($columns as $column) {
            echo '<tr>';
            echo '<td>' . $column['Field'] . '</td>';
            echo '<td>' . $column['Type'] . '</td>';
            echo '<td>' . $column['Null'] . '</td>';
            echo '<td>' . $column['Key'] . '</td>';
            echo '<td>' . $column['Default'] . '</td>';
            echo '<td>' . $column['Extra'] . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        // Check if there are any records in the table
        $stmt = $pdo->query('SELECT COUNT(*) AS message_count FROM parent_teacher_messages');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>There are {$row['message_count']} messages in the parent_teacher_messages table</p>";
        
        if ($row['message_count'] > 0) {
            // Show the most recent messages
            $stmt = $pdo->query("
                SELECT 
                    ptm.id, 
                    ptm.parent_id, 
                    ptm.teacher_id, 
                    ptm.class_id, 
                    ptm.sender_type, 
                    ptm.message, 
                    ptm.is_read, 
                    ptm.created_at,
                    p.parent_full_name,
                    t.full_name as teacher_name,
                    c.class_name
                FROM parent_teacher_messages ptm
                JOIN parents p ON ptm.parent_id = p.id
                JOIN teachers t ON ptm.teacher_id = t.id
                JOIN classes c ON ptm.class_id = c.id
                ORDER BY ptm.created_at DESC
                LIMIT 10
            ");
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<h2>Recent Messages</h2>';
            echo '<table border="1" cellpadding="5">';
            echo '<tr><th>ID</th><th>Parent</th><th>Teacher</th><th>Class</th><th>Sender</th><th>Message</th><th>Read?</th><th>Time</th></tr>';
            
            foreach ($messages as $message) {
                echo '<tr>';
                echo '<td>' . $message['id'] . '</td>';
                echo '<td>' . $message['parent_full_name'] . ' (ID: ' . $message['parent_id'] . ')</td>';
                echo '<td>' . $message['teacher_name'] . ' (ID: ' . $message['teacher_id'] . ')</td>';
                echo '<td>' . $message['class_name'] . ' (ID: ' . $message['class_id'] . ')</td>';
                echo '<td>' . $message['sender_type'] . '</td>';
                echo '<td>' . htmlspecialchars($message['message']) . '</td>';
                echo '<td>' . ($message['is_read'] ? 'Yes' : 'No') . '</td>';
                echo '<td>' . $message['created_at'] . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p>No messages found. You can create the table and add sample data by running <a href="run_db_setup.php">run_db_setup.php</a></p>';
        }
    } else {
        echo '<p style="color: red;">❌ parent_teacher_messages table does not exist</p>';
        echo '<p>Click <a href="run_db_setup.php">here</a> to create the table</p>';
    }
} catch (PDOException $e) {
    echo '<p style="color: red;">❌ Database error: ' . $e->getMessage() . '</p>';
} catch (Exception $e) {
    echo '<p style="color: red;">❌ Error: ' . $e->getMessage() . '</p>';
}

echo '<p><a href="parent-dashboard.html">Go to Parent Dashboard</a></p>';
?> 