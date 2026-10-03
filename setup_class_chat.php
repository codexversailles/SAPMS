<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once "config.php";

echo "<h1>Setting up Class Chat Table</h1>";

try {
    // Create database connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>Connected to database successfully.</p>";
    
    // Read SQL from file
    $sql = file_get_contents('create_class_chat_table.sql');
    
    // Execute SQL statements
    $conn->exec($sql);
    
    echo "<p>Class chat table has been set up successfully!</p>";
    
    // Check if table exists and show record count
    $stmt = $conn->query("SELECT COUNT(*) FROM class_chat_messages");
    $count = $stmt->fetchColumn();
    
    echo "<p>The class_chat_messages table contains {$count} message(s).</p>";
    
    // Show link to return to dashboard
    echo '<p><a href="teacher-dashboard.html">Return to Dashboard</a></p>';
    
} catch(PDOException $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    
    // Additional troubleshooting help
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "<p><strong>Troubleshooting:</strong> The database '{$dbname}' does not exist. Please create it first.</p>";
    } elseif (strpos($e->getMessage(), "Access denied") !== false) {
        echo "<p><strong>Troubleshooting:</strong> Check your database username and password in config.php.</p>";
    }
}
?> 