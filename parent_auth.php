<?php
session_start();
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'studyhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM parents WHERE email = ?");
        $stmt->execute([$email]);
        $parent = $stmt->fetch();
        
        if ($parent && password_verify($password, $parent['password'])) {
            $_SESSION['parent_id'] = $parent['id'];
            $_SESSION['parent_name'] = $parent['full_name'];
            echo json_encode(['success' => true, 'message' => 'Login successful']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
    }
}

// Handle registration
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
    
    try {
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
        
        // Insert new parent
        $stmt = $pdo->prepare("INSERT INTO parents (parent_full_name, email, password, student_id, student_full_name, relationship) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$parentFullName, $email, $hashedPassword, $studentId, $studentFullName, $relationship]);
        
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
    }
}
?> 