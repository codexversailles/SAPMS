<?php
require_once 'config.php';

// Test file upload
$test_file = [
    'name' => 'test.txt',
    'type' => 'text/plain',
    'tmp_name' => tempnam(sys_get_temp_dir(), 'test'),
    'error' => 0,
    'size' => 5
];

// Create test content
file_put_contents($test_file['tmp_name'], 'test');

// Test assessment creation
$class_id = 1; // Use an existing class ID
$title = 'Test Assessment';
$description = 'This is a test assessment';

try {
    // Start transaction
    $conn->begin_transaction();

    // Insert assessment
    $stmt = $conn->prepare("INSERT INTO assessments (class_id, title, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $class_id, $title, $description);
    $stmt->execute();
    
    $assessment_id = $conn->insert_id;

    // Test file upload
    $upload_dir = 'uploads/assessments/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $unique_name = uniqid() . '_' . $test_file['name'];
    $file_path = $upload_dir . $unique_name;
    
    if (move_uploaded_file($test_file['tmp_name'], $file_path)) {
        $stmt = $conn->prepare("INSERT INTO assessment_files (assessment_id, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $assessment_id, $test_file['name'], $file_path, $test_file['type'], $test_file['size']);
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();
    
    echo "Test successful! Assessment ID: " . $assessment_id;

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "Test failed: " . $e->getMessage();
}

// Clean up
if (file_exists($test_file['tmp_name'])) {
    unlink($test_file['tmp_name']);
}
?> 