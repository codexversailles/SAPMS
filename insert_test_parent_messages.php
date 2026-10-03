<?php
// Initialize session
session_start();

// Include database connection
require_once 'db_connect.php';

// For testing purposes, set a teacher ID if not already set
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['teacher_id'] = 8; // Use an actual teacher ID from your database
}

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Test Parent Messages</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #3498db; }
        .section { margin-bottom: 30px; border: 1px solid #eee; padding: 15px; border-radius: 8px; }
        h2 { color: #2c3e50; margin-top: 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .success { color: green; }
        .error { color: red; }
        .btn { 
            display: inline-block; 
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Insert Test Parent Messages</h1>';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insert_test_data'])) {
    try {
        // Get a sample parent and class for testing
        // First, get a sample class
        $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE teacher_id = :teacher_id LIMIT 1");
        $stmt->execute(['teacher_id' => $_SESSION['teacher_id']]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$class) {
            echo '<div class="section"><p class="error">Error: No classes found for this teacher. Please create a class first.</p></div>';
            exit;
        }
        
        // Next, get a sample parent
        $stmt = $pdo->query("SELECT id, parent_full_name FROM parents LIMIT 1");
        $parent = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$parent) {
            echo '<div class="section"><p class="error">Error: No parents found in the database. Please add a parent first.</p></div>';
            exit;
        }
        
        echo '<div class="section">
            <h2>Test Data</h2>
            <p>Using the following data for test messages:</p>
            <ul>
                <li><strong>Teacher ID:</strong> ' . $_SESSION['teacher_id'] . '</li>
                <li><strong>Parent:</strong> ' . $parent['id'] . ' - ' . $parent['parent_full_name'] . '</li>
                <li><strong>Class:</strong> ' . $class['id'] . ' - ' . $class['class_name'] . '</li>
            </ul>';
        
        // Insert test messages
        $testMessages = [
            [
                'sender_type' => 'parent',
                'message' => 'Hello teacher, I would like to discuss my child\'s progress.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'is_read' => 0
            ],
            [
                'sender_type' => 'teacher',
                'message' => 'Of course, I\'m available for a meeting this week. How about Thursday?',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'is_read' => 1
            ],
            [
                'sender_type' => 'parent',
                'message' => 'Thursday works for me. What time would be best?',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'is_read' => 0
            ],
            [
                'sender_type' => 'parent',
                'message' => 'Also, I wanted to ask about the upcoming field trip.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours')),
                'is_read' => 0
            ],
            [
                'sender_type' => 'teacher',
                'message' => 'We can meet at 3:30 PM on Thursday. Regarding the field trip, I\'ll send you the details shortly.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'is_read' => 1
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO parent_teacher_messages (
                parent_id, 
                teacher_id, 
                class_id, 
                sender_type, 
                message, 
                is_read, 
                created_at
            ) VALUES (
                :parent_id,
                :teacher_id,
                :class_id,
                :sender_type,
                :message,
                :is_read,
                :created_at
            )
        ");
        
        $insertedCount = 0;
        foreach ($testMessages as $message) {
            $stmt->execute([
                'parent_id' => $parent['id'],
                'teacher_id' => $_SESSION['teacher_id'],
                'class_id' => $class['id'],
                'sender_type' => $message['sender_type'],
                'message' => $message['message'],
                'is_read' => $message['is_read'],
                'created_at' => $message['created_at']
            ]);
            $insertedCount++;
        }
        
        echo '<p class="success">Successfully inserted ' . $insertedCount . ' test messages!</p>';
        echo '</div>';
        
    } catch (PDOException $e) {
        echo '<div class="section"><p class="error">Database error: ' . $e->getMessage() . '</p></div>';
    }
}

// Display the form
echo '<div class="section">
    <h2>Insert Test Data</h2>
    <p>Click the button below to insert test parent-teacher messages into the database.</p>
    <form method="post" action="">
        <input type="hidden" name="insert_test_data" value="1">
        <button type="submit" class="btn">Insert Test Data</button>
        <a href="test_parent_messages.php" class="btn" style="background-color: #27ae60;">Test Messages</a>
        <a href="teacher-dashboard.html" class="btn" style="background-color: #f39c12;">Go to Dashboard</a>
    </form>
</div>';

echo '</div>
</body>
</html>';
?> 