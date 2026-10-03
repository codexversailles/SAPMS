<?php
// This script manually tests the get_class_details.php endpoint for each class ID

// Start a session and set teacher_id
session_start();
$_SESSION['teacher_id'] = 8; // Use the actual teacher ID from your database

// Include database connection
require_once 'db_connect.php';

// Get all class IDs for this teacher
$stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE teacher_id = ?");
$stmt->execute([$_SESSION['teacher_id']]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output format options
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

if ($format === 'json') {
    header('Content-Type: application/json');
    $results = [];
} else {
    header('Content-Type: text/html');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Class Details Test</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow: auto; }
            h2 { margin-top: 30px; }
            .error { color: red; }
            .success { color: green; }
        </style>
    </head>
    <body>
        <h1>Class Details Test Results</h1>';
}

foreach ($classes as $class) {
    $class_id = $class['id'];
    
    // We'll use direct PHP execution instead of CURL for more reliable local testing
    
    // Save the original session data and GET parameters
    $originalSession = $_SESSION;
    $originalGet = $_GET;
    
    // Set up parameters for get_class_details.php
    $_GET['id'] = $class_id;
    
    // Capture output
    ob_start();
    // Include the file directly (will execute its contents)
    include 'get_class_details.php';
    $response = ob_get_clean();
    
    // Restore original session and GET data
    $_SESSION = $originalSession;
    $_GET = $originalGet;
    
    // Parse the JSON response
    $data = json_decode($response, true);
    
    if ($format === 'json') {
        $results[$class_id] = [
            'class_name' => $class['class_name'],
            'response' => $data,
            'raw_response' => $response
        ];
    } else {
        echo "<h2>Class ID: {$class_id} ({$class['class_name']})</h2>";
        
        if ($data === null) {
            echo '<p class="error">Error: Invalid JSON response</p>';
            echo '<pre>' . htmlspecialchars($response) . '</pre>';
        } else {
            echo '<p>Response:</p>';
            echo '<pre>' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . '</pre>';
            
            if (isset($data['success']) && $data['success'] && isset($data['class'])) {
                echo '<p class="success">✅ Response looks valid</p>';
                
                // Check for required fields
                $requiredFields = ['class_name', 'class_code', 'created_at', 'student_count'];
                $missingFields = [];
                
                foreach ($requiredFields as $field) {
                    if (!isset($data['class'][$field]) || $data['class'][$field] === null) {
                        $missingFields[] = $field;
                    }
                }
                
                if (!empty($missingFields)) {
                    echo '<p class="error">⚠️ Missing fields: ' . implode(', ', $missingFields) . '</p>';
                }
            } else {
                echo '<p class="error">❌ Response is not successful or missing class data</p>';
                if (isset($data['message'])) {
                    echo '<p>Error message: ' . htmlspecialchars($data['message']) . '</p>';
                }
            }
        }
    }
}

if ($format === 'json') {
    echo json_encode($results, JSON_PRETTY_PRINT);
} else {
    echo '</body>
    </html>';
}
?> 