<?php
// Initialize session
session_start();

// For testing purposes, we'll set a teacher ID if not already set
if (!isset($_SESSION['teacher_id'])) {
    $_SESSION['teacher_id'] = 8; // Use an actual teacher ID from your database
}

// Include database connection
require_once 'db_connect.php';

// Output header
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Messages Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #3498db; }
        .section { margin-bottom: 30px; border: 1px solid #eee; padding: 15px; border-radius: 8px; }
        h2 { color: #2c3e50; margin-top: 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Parent Messages Test</h1>';

// Test database connection
echo '<div class="section">
    <h2>Database Connection</h2>';
try {
    $pdo->query("SELECT 1");
    echo '<p class="success">Database connection successful!</p>';
} catch (Exception $e) {
    echo '<p class="error">Database connection failed: ' . $e->getMessage() . '</p>';
}
echo '</div>';

// Test session
echo '<div class="section">
    <h2>Session Status</h2>
    <pre>' . print_r($_SESSION, true) . '</pre>
</div>';

// Test recent parent messages endpoint
echo '<div class="section">
    <h2>Recent Parent Messages Endpoint</h2>';
try {
    $response = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/get_recent_parent_messages.php');
    $data = json_decode($response, true);
    
    if ($data['success']) {
        echo '<p class="success">API call successful! Retrieved ' . count($data['messages']) . ' messages.</p>';
        echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre>';
    } else {
        echo '<p class="error">API call failed: ' . $data['message'] . '</p>';
    }
} catch (Exception $e) {
    echo '<p class="error">Error making API call: ' . $e->getMessage() . '</p>';
}
echo '</div>';

// Test parent_teacher_messages table
echo '<div class="section">
    <h2>Parent Teacher Messages Table</h2>';
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM parent_teacher_messages");
    $count = $stmt->fetchColumn();
    echo '<p>Total messages in database: ' . $count . '</p>';
    
    $stmt = $pdo->prepare("
        SELECT * FROM parent_teacher_messages 
        WHERE teacher_id = :teacher_id 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute(['teacher_id' => $_SESSION['teacher_id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($messages) > 0) {
        echo '<p class="success">Found ' . count($messages) . ' recent messages for this teacher:</p>';
        echo '<pre>' . json_encode($messages, JSON_PRETTY_PRINT) . '</pre>';
    } else {
        echo '<p>No messages found for this teacher. You may need to add some test data.</p>';
    }
} catch (Exception $e) {
    echo '<p class="error">Database query error: ' . $e->getMessage() . '</p>';
}
echo '</div>';

// Output footer
echo '</div>
</body>
</html>';
?> 