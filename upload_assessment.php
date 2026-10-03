<?php
session_start();
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in as a teacher
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in as teacher']);
    exit;
}

try {
    // Get form data
    $classId = $_POST['class_id'] ?? null;
    $title = $_POST['title'] ?? null;
    $description = $_POST['description'] ?? null;
    $dueDate = $_POST['due_date'] ?? null;
    $maxScore = $_POST['max_score'] ?? 100; // Default to 100 if not provided

    // Validate required fields
    if (empty($classId) || empty($title)) {
        throw new Exception('Missing required fields');
    }

    // Validate max score
    if (!is_numeric($maxScore) || $maxScore < 1) {
        $maxScore = 100; // Set to default if invalid
    }

    // Start transaction
    $pdo->beginTransaction();

    // Insert assessment
    $stmt = $pdo->prepare("
        INSERT INTO assessments (class_id, title, description, due_date, max_score)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $classId,
        $title,
        $description,
        $dueDate,
        $maxScore
    ]);

    $assessmentId = $pdo->lastInsertId();

    // Handle file uploads if any
    if (!empty($_FILES['files'])) {
        $uploadDir = 'uploads/assessments/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            $fileName = $_FILES['files']['name'][$key];
            $fileType = $_FILES['files']['type'][$key];
            $fileSize = $_FILES['files']['size'][$key];
            
            // Generate unique filename
            $uniqueFileName = uniqid() . '_' . $fileName;
            $filePath = $uploadDir . $uniqueFileName;

            // Move uploaded file
            if (move_uploaded_file($tmp_name, $filePath)) {
                // Insert file record
                $stmt = $pdo->prepare("
                    INSERT INTO assessment_files (assessment_id, file_name, file_path, file_type, file_size)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$assessmentId, $fileName, $filePath, $fileType, $fileSize]);
            }
        }
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Assessment created successfully',
        'assessment_id' => $assessmentId
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?> 