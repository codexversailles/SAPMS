<?php
// Prevent any output before our JSON response
ob_start();

// Include database connection
require_once 'config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Write to a log file for debugging
$logFile = 'class_creation_log.txt';
file_put_contents($logFile, "Request received at: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Set error handling to catch any unexpected issues
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    global $logFile;
    
    // Log the error
    $errorMsg = "PHP Error: [$errno] $errstr in $errfile on line $errline\n";
    file_put_contents($logFile, $errorMsg, FILE_APPEND);
    
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Return JSON error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'PHP Error: ' . $errstr,
        'debug' => [
            'file' => $errfile,
            'line' => $errline
        ]
    ]);
    exit;
});

// Set headers for JSON response
header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents($logFile, "Invalid request method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Only POST is allowed.'
    ]);
    exit;
}

// Get the POST data
$rawData = file_get_contents('php://input');
file_put_contents($logFile, "Raw request data: " . $rawData . "\n", FILE_APPEND);

$data = json_decode($rawData, true);
file_put_contents($logFile, "Decoded data: " . print_r($data, true) . "\n", FILE_APPEND);

// Validate required fields
if (!isset($data['class_name']) || empty($data['class_name'])) {
    file_put_contents($logFile, "Missing class_name field\n", FILE_APPEND);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Class name is required'
    ]);
    exit;
}

if (!isset($data['class_code']) || empty($data['class_code'])) {
    file_put_contents($logFile, "Missing class_code field\n", FILE_APPEND);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Class code is required'
    ]);
    exit;
}

// Start session if not already started (to get teacher ID from session)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log session data
file_put_contents($logFile, "Session data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Check if teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    file_put_contents($logFile, "No teacher_id in session\n", FILE_APPEND);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in as a teacher to create a class'
    ]);
    exit;
}

$class_name = $data['class_name'];
$class_code = $data['class_code'];
$teacher_id = $_SESSION['teacher_id'];

file_put_contents($logFile, "Processing with: class_name=$class_name, class_code=$class_code, teacher_id=$teacher_id\n", FILE_APPEND);

try {
    // Check if class code already exists
    $check_query = "SELECT id FROM classes WHERE class_code = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $class_code);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        file_put_contents($logFile, "Class code already exists\n", FILE_APPEND);
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Class code already exists. Please try again with a new code.'
        ]);
        exit;
    }
    
    // Insert the new class
    $insert_query = "INSERT INTO classes (class_name, class_code, teacher_id) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("ssi", $class_name, $class_code, $teacher_id);
    $result = $insert_stmt->execute();
    
    if ($result) {
        $class_id = $conn->insert_id;
        file_put_contents($logFile, "Class created successfully with ID: $class_id\n", FILE_APPEND);
        
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Class created successfully',
            'data' => [
                'class_id' => $class_id,
                'class_name' => $class_name,
                'class_code' => $class_code
            ]
        ]);
    } else {
        file_put_contents($logFile, "Failed to create class: " . $conn->error . "\n", FILE_APPEND);
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create class: ' . $conn->error
        ]);
    }
} catch (Exception $e) {
    file_put_contents($logFile, "Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    // Clear any buffered output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
    ]);
}
?> 