<?php
// Set maximum error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

// Log request details
error_log("Request received: " . json_encode($_POST));

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Simple echo back test
$response = [
    'success' => true,
    'message' => 'Test response successful',
    'received' => $_POST,
    'time' => date('Y-m-d H:i:s')
];

// Clear any buffered output
ob_end_clean();

// Log response 
error_log("Responding with: " . json_encode($response));

// Output JSON
echo json_encode($response);
exit;
?> 