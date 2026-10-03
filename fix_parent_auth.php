<?php
// Start output buffering to prevent premature output
ob_start();

// Turn off error display in production but log them
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start the session if not already started
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

    // Handle registration (test with this version that matches actual DB structure)
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
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit;
        }
        
        // Verify email verification was completed if using verification
        /*
        if (!isset($_SESSION['email_verified']) || $_SESSION['email_verified'] !== true || !isset($_SESSION['verification_email']) || $_SESSION['verification_email'] !== $email) {
            echo json_encode(['success' => false, 'message' => 'Email verification is required']);
            exit;
        }
        */
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM parents WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already registered']);
            exit;
        }

        // Verify student exists with matching ID and name
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ? AND full_name = ?");
        $stmt->execute([$studentId, $studentFullName]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Invalid student ID or name. Please ensure the student is registered first.']);
            exit;
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Get columns from parents table to identify correct column names
        $stmt = $pdo->prepare("DESCRIBE parents");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        echo json_encode(['columns' => $columns]);
        
        // Insert new parent - check if student_id/student_full_name fields exist
        // This version uses parent_full_name field which seems to be the actual field name
        $stmt = $pdo->prepare("INSERT INTO parents (parent_full_name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$parentFullName, $email, $hashedPassword]);
        
        // Get the newly created parent ID
        $parentId = $pdo->lastInsertId();
        
        // Create parent-child relationship in the correct table
        $stmt = $pdo->prepare("INSERT INTO parent_children (parent_id, student_id, relationship) VALUES (?, ?, ?)");
        $stmt->execute([$parentId, $studentId, $relationship]);
        
        // Clear verification session
        unset($_SESSION['email_verified']);
        unset($_SESSION['verification_email']);
        
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method or action']);
    }
} catch(PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch(Exception $e) {
    error_log('Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

// Flush output buffer
ob_end_flush();
?> 