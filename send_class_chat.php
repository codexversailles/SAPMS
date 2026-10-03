<?php
// File: send_class_chat.php
// This file handles sending a message in the class chat

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Skip authentication for testing
/*
if (!isset($_SESSION["teacher_id"])) {
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}
*/

// Include database configuration
require_once "config.php";

// Get POST data
$class_id = isset($_POST["class_id"]) ? intval($_POST["class_id"]) : 0;
$message = isset($_POST["message"]) ? trim($_POST["message"]) : "";
$sender_type = 'teacher'; // Always teacher in this context
$sender_id = isset($_SESSION["teacher_id"]) ? $_SESSION["teacher_id"] : 1; // Fallback to teacher ID 1 for testing

// Log received data
$debug_info = [
    'post_data' => $_POST,
    'class_id' => $class_id,
    'message' => $message,
    'sender_type' => $sender_type,
    'sender_id' => $sender_id,
    'session' => isset($_SESSION["teacher_id"]) ? $_SESSION["teacher_id"] : "Not set"
];

// Validate input
if ($class_id <= 0) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false, 
        "message" => "Invalid class ID",
        "debug" => $debug_info
    ]);
    exit;
}

if (empty($message)) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false, 
        "message" => "Message cannot be empty",
        "debug" => $debug_info
    ]);
    exit;
}

try {
    // Create database connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Insert message
    $stmt = $conn->prepare("
        INSERT INTO class_chat_messages (class_id, sender_id, sender_type, message)
        VALUES (?, ?, ?, ?)
    ");
    $result = $stmt->execute([$class_id, $sender_id, $sender_type, $message]);
    
    if ($result) {
        $messageId = $conn->lastInsertId();
        
        // Get the newly inserted message
        $stmt = $conn->prepare("
            SELECT * FROM class_chat_messages WHERE id = ?
        ");
        $stmt->execute([$messageId]);
        $newMessage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Return success response with the new message
        header("Content-Type: application/json");
        echo json_encode([
            "success" => true, 
            "message" => "Message sent successfully",
            "messageData" => $newMessage,
            "debug" => $debug_info
        ]);
    } else {
        header("Content-Type: application/json");
        echo json_encode([
            "success" => false, 
            "message" => "Failed to send message",
            "debug" => $debug_info
        ]);
    }
    
} catch(PDOException $e) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false, 
        "message" => "Database error: " . $e->getMessage(),
        "debug" => [
            "error_code" => $e->getCode(),
            "trace" => $e->getTraceAsString(),
            "request_data" => $debug_info
        ]
    ]);
}
?> 