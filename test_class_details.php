<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set the class ID to test
$class_id = 4; // Using Chemistry class as an example

// URL encode parameters
$params = http_build_query(['class_id' => $class_id]);

// Build the URL
$url = "get_student_class_details.php?{$params}";

echo "<h1>Testing Class Details API</h1>";
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
    
    if (isset($data['error'])) {
        echo "<p>Error: {$data['error']}</p>";
    }
    
    echo "<h3>Lessons (" . count($data['lessons']) . ")</h3>";
    if (!empty($data['lessons'])) {
        echo "<ul>";
        foreach ($data['lessons'] as $lesson) {
            echo "<li>{$lesson['title']} - {$lesson['description']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No lessons found.</p>";
    }
    
    echo "<h3>Assessments (" . count($data['assessments']) . ")</h3>";
    if (!empty($data['assessments'])) {
        echo "<ul>";
        foreach ($data['assessments'] as $assessment) {
            echo "<li>{$assessment['title']} - {$assessment['description']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No assessments found.</p>";
    }
} else {
    echo "<p>Failed to parse JSON response.</p>";
}
?> 