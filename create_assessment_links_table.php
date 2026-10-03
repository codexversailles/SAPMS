<?php
require_once 'config.php';

try {
    // SQL to create assessment_links table
    $sql = "
    CREATE TABLE IF NOT EXISTS assessment_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        assessment_id INT NOT NULL,
        link_title VARCHAR(255) NOT NULL,
        link_url VARCHAR(2048) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    // Execute the SQL
    $pdo->exec($sql);
    
    echo "Assessment links table created successfully!";
} catch(PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?> 