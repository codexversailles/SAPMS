<?php
// Start the session
session_start();

// Set the teacher ID in the session
$_SESSION['teacher_id'] = 8; // Use the actual teacher ID from your database

// Clear any student session if it exists
if (isset($_SESSION['student_id'])) {
    unset($_SESSION['student_id']);
}

// Output result
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Session Set</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="success">
        <h2>Teacher Session Set Successfully</h2>
        <p>You are now logged in as a teacher with ID: <?php echo $_SESSION['teacher_id']; ?></p>
    </div>
    
    <h3>Current Session Data:</h3>
    <pre><?php print_r($_SESSION); ?></pre>
    
    <a href="class_view_test.html">Go to Class Tester</a>
</body>
</html> 