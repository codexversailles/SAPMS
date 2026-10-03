<?php
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'studyhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test 1: Check if announcements table exists and has records
    $stmt = $pdo->query("SHOW TABLES LIKE 'announcements'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo json_encode([
            'success' => false,
            'message' => 'Announcements table does not exist'
        ]);
        exit;
    }
    
    // Test 2: Count all announcements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM announcements");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Test 3: Get all announcements with details
    $stmt = $pdo->query("
        SELECT a.*, c.class_name, t.full_name as teacher_name
        FROM announcements a
        JOIN classes c ON a.class_id = c.id
        JOIN teachers t ON a.teacher_id = t.id
        ORDER BY a.created_at DESC
    ");
    $allAnnouncements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test 4: Check the structure of the announcements table
    $stmt = $pdo->query("DESCRIBE announcements");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the test results
    echo json_encode([
        'success' => true,
        'table_exists' => $tableExists,
        'total_count' => $count,
        'announcements' => $allAnnouncements,
        'table_structure' => $structure
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 