<?php
// Start session to get teacher ID
session_start();

// Check if user is logged in
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get class ID from request
$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($class_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
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

    // Get class details with student count
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.class_name,
            c.class_code,
            c.created_at,
            COUNT(cs.id) as student_count
        FROM classes c
        LEFT JOIN class_students cs ON c.id = cs.class_id
        WHERE c.id = ? AND c.teacher_id = ?
        GROUP BY c.id
    ");

    $stmt->execute([$class_id, $_SESSION['teacher_id']]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Class not found']);
        exit;
    }

    // Get enrolled students for this class
    $stmt = $pdo->prepare("
        SELECT s.id, s.full_name as name, s.email, s.student_id, cs.enrolled_at, cs.status
        FROM class_students cs
        JOIN students s ON cs.student_id = s.id
        WHERE cs.class_id = ?
        ORDER BY s.full_name
    ");
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $response = [
        'success' => true,
        'class' => [
            'id' => $class['id'],
            'class_name' => $class['class_name'],
            'class_code' => $class['class_code'],
            'created_at' => $class['created_at'],
            'student_count' => intval($class['student_count']),
            'students' => $students
        ]
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 