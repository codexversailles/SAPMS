<?php
// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Check if session exists
$sessionExists = session_status() === PHP_SESSION_ACTIVE;

// Check if teacher ID is in session
$teacherLoggedIn = isset($_SESSION['teacher_id']);

// Get all session data
$sessionData = $_SESSION;

// Return session status
echo json_encode([
    'success' => true,
    'session_exists' => $sessionExists,
    'teacher_logged_in' => $teacherLoggedIn,
    'teacher_id' => $teacherLoggedIn ? $_SESSION['teacher_id'] : null,
    'session_id' => session_id(),
    'session_data' => $sessionData
]);
?> 