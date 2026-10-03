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

try {
    // Get parent information
    $stmt = $pdo->prepare("SELECT id, parent_full_name, email FROM parents WHERE id = ?");
    $stmt->execute([$parentId]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parent) {
        echo json_encode(['success' => false, 'message' => 'Parent not found']);
        exit;
    }
    
    // Get all children of the parent
    $stmt = $pdo->prepare("
        SELECT pc.id, pc.student_id, pc.relationship, s.full_name 
        FROM parent_children pc
        JOIN students s ON pc.student_id = s.student_id
        WHERE pc.parent_id = ?
    ");
    $stmt->execute([$parentId]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If there are children, add the first child's info to the parent object for compatibility
    if (count($children) > 0) {
        $parent['student_id'] = $children[0]['student_id'];
        $parent['student_full_name'] = $children[0]['full_name'];
        $parent['relationship'] = $children[0]['relationship'];
    }
    
    // Return the parent data and all children
    echo json_encode([
        'success' => true,
        'parent' => $parent,
        'children' => $children
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching data: ' . $e->getMessage()]);
}
?> 