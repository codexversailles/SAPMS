<?php
session_start();
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$classCode = $data['classCode'] ?? '';

if (empty($classCode)) {
    echo json_encode(['success' => false, 'message' => 'Class code is required']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get class ID from class code
    $stmt = $pdo->prepare("SELECT id FROM classes WHERE class_code = ? AND is_active = 1");
    $stmt->execute([$classCode]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$class) {
        throw new Exception('Invalid class code');
    }

    // Check if student is already enrolled
    $stmt = $pdo->prepare("SELECT id FROM class_students WHERE class_id = ? AND student_id = ?");
    $stmt->execute([$class['id'], $_SESSION['student_id']]);
    if ($stmt->fetch()) {
        throw new Exception('You are already enrolled in this class');
    }

    // Add student to class
    $stmt = $pdo->prepare("INSERT INTO class_students (class_id, student_id, status) VALUES (?, ?, 'active')");
    $result = $stmt->execute([$class['id'], $_SESSION['student_id']]);

    if (!$result) {
        throw new Exception('Failed to join class: Database error');
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Successfully joined the class',
        'debug' => [
            'class_id' => $class['id'],
            'student_id' => $_SESSION['student_id']
        ]
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} 