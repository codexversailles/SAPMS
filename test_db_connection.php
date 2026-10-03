<?php
// Test the database connection
echo '<h1>Testing Database Connection</h1>';

try {
    // Include the database connection file
    require_once 'db_connect.php';
    
    if (!isset($pdo)) {
        throw new Exception('Database connection variable $pdo is not defined');
    }
    
    // Test the connection by querying the database
    $result = $pdo->query('SELECT 1');
    
    if ($result) {
        echo '<p style="color: green;">✅ Database connection successful!</p>';
        
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
            
            // Check if there are any records in the table
            $stmt = $pdo->query('SELECT COUNT(*) AS message_count FROM parent_teacher_messages');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo '<p>There are ' . $row['message_count'] . ' messages in the parent_teacher_messages table</p>';
        } else {
            echo '<p style="color: red;">❌ parent_teacher_messages table does not exist</p>';
            echo '<p>Click <a href="run_db_setup.php">here</a> to create the table</p>';
        }
    } else {
        echo '<p style="color: red;">❌ Failed to execute test query</p>';
    }
} catch (PDOException $e) {
    echo '<p style="color: red;">❌ Database connection failed: ' . $e->getMessage() . '</p>';
} catch (Exception $e) {
    echo '<p style="color: red;">❌ Error: ' . $e->getMessage() . '</p>';
}

echo '<p><a href="parent-dashboard.html">Go to Parent Dashboard</a></p>'; 