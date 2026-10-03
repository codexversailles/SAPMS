<?php
require_once 'config.php';

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Check if email_verification table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'email_verification'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "The email_verification table exists.<br>";
        
        // Count records in the table
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM email_verification");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "There are " . $result['count'] . " records in the email_verification table.<br>";
        
        // Check if any code has been stored recently
        $stmt = $pdo->query("SELECT * FROM email_verification WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY) ORDER BY created_at DESC LIMIT 5");
        $recentCodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($recentCodes) > 0) {
            echo "Recent verification codes:<br>";
            foreach ($recentCodes as $code) {
                echo "Email: " . $code['email'] . " | Code: " . $code['code'] . " | Created: " . $code['created_at'] . "<br>";
            }
        } else {
            echo "No recent verification codes found.<br>";
        }
    } else {
        echo "The email_verification table does not exist.<br>";
        
        // Create the table
        echo "Creating email_verification table...<br>";
        $pdo->exec("
            CREATE TABLE `email_verification` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `email` varchar(100) NOT NULL,
              `code` varchar(10) NOT NULL,
              `expires_at` datetime NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `email_idx` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        echo "Table created successfully.<br>";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?> 