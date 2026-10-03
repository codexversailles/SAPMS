<?php
// Set error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'studyhub';
    $username = 'root';
    $password = '';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Debug info
    $debug = [
        'post' => $_POST,
        'session' => $_SESSION
    ];

    // Handle registration (bypassing verification)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
        $parentFullName = $_POST['fullName'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];
        $studentFullName = $_POST['childName'];
        $studentId = $_POST['studentId'];
        $relationship = $_POST['relationship'];
        
        // Validate password match
        if ($password !== $confirmPassword) {
            echo json_encode([
                'success' => false, 
                'message' => 'Passwords do not match',
                'debug' => $debug
            ]);
            exit;
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM parents WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode([
                'success' => false, 
                'message' => 'Email already registered',
                'debug' => $debug
            ]);
            exit;
        }

        // Verify student exists with matching ID and name
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ? AND full_name = ?");
        $stmt->execute([$studentId, $studentFullName]);
        $student = $stmt->fetch();
        if (!$student) {
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid student ID or name. Please ensure the student is registered first.',
                'debug' => $debug
            ]);
            exit;
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new parent with only the fields that exist in the table
        $stmt = $pdo->prepare("INSERT INTO parents (parent_full_name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$parentFullName, $email, $hashedPassword]);
        
        // Get the newly created parent ID
        $parentId = $pdo->lastInsertId();
        
        // Create parent-child relationship in the parent_children table
        $stmt = $pdo->prepare("INSERT INTO parent_children (parent_id, student_id, relationship) VALUES (?, ?, ?)");
        $stmt->execute([$parentId, $studentId, $relationship]);
        
        // Debug info for successful registration
        $debug['success'] = true;
        $debug['parent_id'] = $parentId;
        $debug['student_data'] = $student;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Registration successful! (Verification bypassed for testing)',
            'debug' => $debug
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid request method or missing action parameter',
            'debug' => $debug
        ]);
    }
} catch(PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'debug' => $debug ?? []
    ]);
} catch(Exception $e) {
    error_log('Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred: ' . $e->getMessage(),
        'debug' => $debug ?? []
    ]);
}
?> 