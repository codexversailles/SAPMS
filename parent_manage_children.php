<?php
session_start();
header('Content-Type: application/json');

// Check if the parent is logged in
if (!isset($_SESSION['parent_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

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

$parentId = $_SESSION['parent_id'];

// Get all children of the parent
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("
            SELECT pc.id, pc.student_id, pc.relationship, s.full_name 
            FROM parent_children pc
            JOIN students s ON pc.student_id = s.student_id
            WHERE pc.parent_id = ?
        ");
        $stmt->execute([$parentId]);
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'children' => $children]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching children: ' . $e->getMessage()]);
    }
}

// Add a new child to the parent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_child') {
    $studentId = $_POST['studentId'];
    $relationship = $_POST['relationship'];
    
    try {
        // Verify student exists
        $stmt = $pdo->prepare("SELECT full_name FROM students WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();
        
        if (!$student) {
            echo json_encode(['success' => false, 'message' => 'Student ID not found']);
            exit;
        }
        
        // Check if this student is already linked to this parent
        $stmt = $pdo->prepare("SELECT id FROM parent_children WHERE parent_id = ? AND student_id = ?");
        $stmt->execute([$parentId, $studentId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'This child is already linked to your account']);
            exit;
        }
        
        // Add the child to the parent
        $stmt = $pdo->prepare("INSERT INTO parent_children (parent_id, student_id, relationship) VALUES (?, ?, ?)");
        $stmt->execute([$parentId, $studentId, $relationship]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Child added successfully',
            'child' => [
                'id' => $pdo->lastInsertId(),
                'student_id' => $studentId,
                'relationship' => $relationship,
                'full_name' => $student['full_name']
            ]
        ]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error adding child: ' . $e->getMessage()]);
    }
}

// Remove a child from the parent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_child') {
    $childId = $_POST['childId'];
    
    try {
        // Verify this child belongs to this parent
        $stmt = $pdo->prepare("SELECT id FROM parent_children WHERE id = ? AND parent_id = ?");
        $stmt->execute([$childId, $parentId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Child not found or not authorized']);
            exit;
        }
        
        // Delete the child-parent relationship
        $stmt = $pdo->prepare("DELETE FROM parent_children WHERE id = ?");
        $stmt->execute([$childId]);
        
        echo json_encode(['success' => true, 'message' => 'Child removed successfully']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error removing child: ' . $e->getMessage()]);
    }
}

// Check if the file exists first
?> 