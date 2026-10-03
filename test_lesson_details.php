<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set the lesson ID to test
$lesson_id = 1; // Replace with a valid lesson ID from your database

// URL encode parameters
$params = http_build_query(['id' => $lesson_id]);

// Build the URL
$url = "get_student_lesson_details.php?{$params}";

echo "<h1>Testing Lesson Details API</h1>";
echo "<p>Testing URL: <code>{$url}</code></p>";

// Make the request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>Response Status</h2>";
echo "<p>HTTP Status: {$status}</p>";

echo "<h2>Raw Response</h2>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

echo "<h2>Parsed Response</h2>";
$data = json_decode($response, true);
if ($data) {
    echo "<p>Success: " . ($data['success'] ? "Yes" : "No") . "</p>";
    
    if (isset($data['message'])) {
        echo "<p>Message: {$data['message']}</p>";
    }
    
    if (isset($data['data'])) {
        echo "<h3>Lesson Details</h3>";
        echo "<ul>";
        foreach ($data['data'] as $key => $value) {
            if ($key == 'files') {
                echo "<li>Files: " . count($value) . " file(s) attached</li>";
                if (count($value) > 0) {
                    echo "<ul>";
                    foreach ($value as $file) {
                        echo "<li>{$file['file_name']}</li>";
                    }
                    echo "</ul>";
                }
            } else {
                echo "<li>{$key}: " . htmlspecialchars(print_r($value, true)) . "</li>";
            }
        }
        echo "</ul>";
    }
} else {
    echo "<p>Failed to parse JSON response.</p>";
}
?> 