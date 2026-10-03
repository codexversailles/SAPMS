<?php
session_start();
header('Content-Type: application/json');

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Return success response
echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
?> 