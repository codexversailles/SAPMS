<?php
// File: get_class_chat.php
// This file retrieves chat messages for a specific class

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Check if config.php exists
if (!file_exists("config.php")) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false, 
        "message" => "Configuration file not found",
        "debug" => "The config.php file does not exist"
    ]);
    exit;
}

// Include database configuration
require_once "config.php";

// Return debug response if connection variables aren't defined
if (!isset($servername) || !isset($username) || !isset($password) || !isset($dbname)) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false, 
        "message" => "Database configuration is incomplete",
        "debug" => "Missing database credentials in config.php"
    ]);
    exit;
}

// Skip authentication for debugging
// Comment this section out once we fix the initial connection issues
/*
if (!isset($_SESSION["teacher_id"])) {
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}
*/

// For testing, use a default class ID if none is provided
$class_id = isset($_GET["class_id"]) ? intval($_GET["class_id"]) : 3; // Using 3 as default (from SQL dump)

try {
    // Test database connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // For testing purposes, return some basic data first
    $stmt = $conn->prepare("SELECT COUNT(*) FROM class_chat_messages WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $count = $stmt->fetchColumn();
    
    // Get messages for this class with simpler query
    $stmt = $conn->prepare("
        SELECT * FROM class_chat_messages 
        WHERE class_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$class_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response with messages and debug info
    header("Content-Type: application/json");
    echo json_encode([
        "success" => true, 
        "messages" => $messages,
        "debug" => [
            "message_count" => $count,
            "class_id" => $class_id,
            "session" => isset($_SESSION["teacher_id"]) ? $_SESSION["teacher_id"] : "Not set"
        ]
    ]);
    
} catch(PDOException $e) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false, 
        "message" => "Database error: " . $e->getMessage(),
        "debug" => [
            "error_code" => $e->getCode(),
            "trace" => $e->getTraceAsString()
        ]
    ]);
}
?> 