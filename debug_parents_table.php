<?php
// Set error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = 'localhost';
$dbname = 'studyhub';
$username = 'root';
$password = '';

try {
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Parents Table Structure</h2>";
    
    // Get table structure
    $stmt = $pdo->prepare("DESCRIBE parents");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    echo "<h2>Sample Parent Records</h2>";
    
    // Get sample data
    $stmt = $pdo->prepare("SELECT * FROM parents LIMIT 3");
    $stmt->execute();
    $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($parents);
    echo "</pre>";
    
    echo "<h2>Parent_Children Table Structure</h2>";
    
    // Get table structure of parent_children
    $stmt = $pdo->prepare("DESCRIBE parent_children");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    echo "<h2>Sample Parent_Children Records</h2>";
    
    // Get sample data
    $stmt = $pdo->prepare("SELECT * FROM parent_children LIMIT 3");
    $stmt->execute();
    $parentChildren = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($parentChildren);
    echo "</pre>";
    
} catch(PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?> 