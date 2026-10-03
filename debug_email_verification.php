<?php
// Set maximum error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Email Verification Debugging Tool</h1>";

// Check if the email_verification table exists
require_once 'config.php';

try {
    echo "<h2>Database Connection</h2>";
    echo "Attempting to connect to database: $dbname on $host<br>";
    
    // Verify database connection
    try {
        $testPdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "✓ Database connection successful<br>";
    } catch (PDOException $e) {
        echo "✗ Database connection failed: " . $e->getMessage() . "<br>";
        die();
    }
    
    // Check verification table
    echo "<h2>Email Verification Table</h2>";
    $stmt = $testPdo->query("SHOW TABLES LIKE 'email_verification'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✓ email_verification table exists<br>";
        
        // Check table structure
        echo "<h3>Table Structure</h3>";
        echo "<pre>";
        $stmt = $testPdo->query("DESCRIBE email_verification");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        print_r($columns);
        echo "</pre>";
        
        // Check for any records
        $stmt = $testPdo->query("SELECT COUNT(*) as total FROM email_verification");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Total records in table: " . $result['total'] . "<br>";
        
        if ($result['total'] > 0) {
            echo "<h3>Recent Records</h3>";
            echo "<pre>";
            $stmt = $testPdo->query("SELECT * FROM email_verification ORDER BY created_at DESC LIMIT 10");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            print_r($records);
            echo "</pre>";
        }
    } else {
        echo "✗ email_verification table does not exist<br>";
        echo "Attempting to create table...<br>";
        
        // Create the table
        $sql = file_get_contents('create_verification_table.sql');
        try {
            $testPdo->exec($sql);
            echo "✓ Table created successfully<br>";
        } catch (PDOException $e) {
            echo "✗ Failed to create table: " . $e->getMessage() . "<br>";
            echo "SQL Query:<br>";
            echo "<pre>$sql</pre>";
        }
    }
    
    // Check PHP Mailer
    echo "<h2>PHPMailer Setup</h2>";
    if (file_exists('PHPMailer/src/PHPMailer.php')) {
        echo "✓ PHPMailer is installed<br>";
        
        // Check if the main files exist
        $requiredFiles = [
            'PHPMailer/src/PHPMailer.php',
            'PHPMailer/src/SMTP.php',
            'PHPMailer/src/Exception.php'
        ];
        $allFilesExist = true;
        foreach ($requiredFiles as $file) {
            if (file_exists($file)) {
                echo "✓ $file exists<br>";
            } else {
                echo "✗ $file is missing<br>";
                $allFilesExist = false;
            }
        }
        
        if (!$allFilesExist) {
            echo "Run the setup_phpmailer.php script to install PHPMailer properly<br>";
        }
    } else {
        echo "✗ PHPMailer is not installed<br>";
        echo "You should run setup_phpmailer.php to install PHPMailer<br>";
        echo "<a href='setup_phpmailer.php' class='button'>Install PHPMailer</a><br>";
    }
    
    // Check email_verification.php
    echo "<h2>Email Verification Script</h2>";
    if (file_exists('email_verification.php')) {
        echo "✓ email_verification.php exists<br>";
        
        // Check Gmail credentials
        $fileContent = file_get_contents('email_verification.php');
        
        // Extract Gmail username
        if (preg_match('/\$gmail_username\s*=\s*[\'"]([^\'"]+)[\'"]/', $fileContent, $matches)) {
            $gmailUsername = $matches[1];
            echo "✓ Gmail username is set: " . htmlspecialchars($gmailUsername) . "<br>";
        } else {
            echo "✗ Gmail username is not set properly<br>";
        }
        
        // Extract Gmail password
        if (preg_match('/\$gmail_password\s*=\s*[\'"]([^\'"]+)[\'"]/', $fileContent, $matches)) {
            $gmailPassword = $matches[1];
            echo "✓ Gmail password is set (hidden for security)<br>";
        } else {
            echo "✗ Gmail password is not set properly<br>";
        }
    } else {
        echo "✗ email_verification.php file does not exist<br>";
    }
    
    // Test verification functionality
    echo "<h2>Test Email Verification</h2>";
    echo "<form method='post' action='debug_email_verification.php'>";
    echo "<input type='email' name='test_email' placeholder='Enter email to test' required>";
    echo "<button type='submit' name='test_send'>Test Send Verification Code</button>";
    echo "</form>";
    
    // Handle test requests
    if (isset($_POST['test_send']) && isset($_POST['test_email'])) {
        $testEmail = $_POST['test_email'];
        echo "<h3>Testing with email: " . htmlspecialchars($testEmail) . "</h3>";
        
        try {
            require_once 'email_verification.php';
            
            // Extract credentials from file
            if (isset($gmailUsername) && isset($gmailPassword)) {
                $emailVerification = new EmailVerification($testPdo, $gmailUsername, $gmailPassword);
                
                // Generate code
                $code = $emailVerification->generateVerificationCode();
                echo "Generated verification code: $code<br>";
                
                // Store code
                if ($emailVerification->storeVerificationCode($testEmail, $code)) {
                    echo "✓ Verification code stored in database<br>";
                    
                    // Verify database record
                    $stmt = $testPdo->prepare("SELECT * FROM email_verification WHERE email = ? ORDER BY created_at DESC LIMIT 1");
                    $stmt->execute([$testEmail]);
                    $record = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($record) {
                        echo "Verification record:<br>";
                        echo "<pre>";
                        print_r($record);
                        echo "</pre>";
                        
                        // Try to send email
                        echo "Attempting to send email...<br>";
                        if ($emailVerification->sendVerificationEmail($testEmail, $code)) {
                            echo "✓ Verification email sent successfully<br>";
                        } else {
                            echo "✗ Failed to send verification email<br>";
                        }
                    } else {
                        echo "✗ Could not find the verification record in the database<br>";
                    }
                } else {
                    echo "✗ Failed to store verification code<br>";
                }
            } else {
                echo "✗ Could not extract Gmail credentials from email_verification.php<br>";
            }
        } catch (Exception $e) {
            echo "✗ Error testing email verification: " . $e->getMessage() . "<br>";
            echo "Stack trace:<br>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }
    
    // Add JavaScript debugging
    echo "<h2>JavaScript Debugging</h2>";
    echo "<p>Add the debug_student_auth.js script to student-auth.html to enable JavaScript debugging.</p>";
    echo "<p>This script adds a debug console that shows AJAX requests and responses.</p>";
    echo "<a href='student-auth.html' target='_blank'>Open Student Auth Page</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 