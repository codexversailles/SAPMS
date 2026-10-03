<?php
require_once 'config.php';
require_once 'email_verification.php';

// Test email - replace with an email you want to test with
$testEmail = "test@example.com";

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<h2>Email Verification Test</h2>";
    
    // Check PHPMailer
    echo "<h3>Checking PHPMailer</h3>";
    if (file_exists('PHPMailer/src/PHPMailer.php')) {
        echo "PHPMailer is installed.<br>";
    } else {
        echo "PHPMailer is NOT installed. Please run setup_phpmailer.php first.<br>";
        die();
    }
    
    // Check Gmail credentials
    echo "<h3>Checking Gmail Credentials</h3>";
    
    // Get Gmail credentials from email_verification.php
    $gmailUsername = '';
    $gmailPassword = '';
    
    $contents = file_get_contents('email_verification.php');
    if (preg_match('/\$gmail_username\s*=\s*[\'"]([^\'"]+)[\'"]/', $contents, $matches)) {
        $gmailUsername = $matches[1];
    }
    
    if (preg_match('/\$gmail_password\s*=\s*[\'"]([^\'"]+)[\'"]/', $contents, $matches)) {
        $gmailPassword = $matches[1];
    }
    
    echo "Gmail Username: " . (empty($gmailUsername) ? "Not found" : $gmailUsername) . "<br>";
    echo "Gmail Password: " . (empty($gmailPassword) ? "Not found" : "Set (hidden)") . "<br>";
    
    if (empty($gmailUsername) || empty($gmailPassword)) {
        echo "Gmail credentials are not properly set in email_verification.php<br>";
        die();
    }
    
    // Test table creation
    echo "<h3>Checking Database Table</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'email_verification'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "The email_verification table does not exist. Creating it now...<br>";
        try {
            $pdo->exec(file_get_contents('create_verification_table.sql'));
            echo "Table created successfully.<br>";
        } catch (PDOException $e) {
            echo "Failed to create table: " . $e->getMessage() . "<br>";
            die();
        }
    } else {
        echo "The email_verification table exists.<br>";
    }
    
    // Test code generation and storage
    echo "<h3>Testing Code Generation and Storage</h3>";
    $emailVerification = new EmailVerification($pdo, $gmailUsername, $gmailPassword);
    $code = $emailVerification->generateVerificationCode();
    echo "Generated verification code: " . $code . "<br>";
    
    if ($emailVerification->storeVerificationCode($testEmail, $code)) {
        echo "Code stored successfully.<br>";
    } else {
        echo "Failed to store code.<br>";
    }
    
    // Test code verification
    echo "<h3>Testing Code Verification</h3>";
    if ($emailVerification->verifyCode($testEmail, $code)) {
        echo "Code verified successfully.<br>";
    } else {
        echo "Code verification failed.<br>";
    }
    
    // Test retrieving verification code
    $stmt = $pdo->prepare("SELECT * FROM email_verification WHERE email = ?");
    $stmt->execute([$testEmail]);
    $verificationRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($verificationRecord) {
        echo "Code retrieval successful. Found code: " . $verificationRecord['code'] . "<br>";
    } else {
        echo "No code found for email: " . $testEmail . " (This is normal if verification succeeded as it deletes the code)<br>";
    }
    
    echo "<h3>Test Complete</h3>";
    echo "If all the above tests passed, your email verification system should be working properly.<br>";
    echo "Would you like to test sending an actual email? <a href='?send_email=1'>Click here</a><br>";
    
    // Test sending email if requested
    if (isset($_GET['send_email'])) {
        echo "<h3>Testing Email Sending</h3>";
        $code = $emailVerification->generateVerificationCode();
        $emailVerification->storeVerificationCode($testEmail, $code);
        
        if ($emailVerification->sendVerificationEmail($testEmail, $code)) {
            echo "Test email sent successfully to " . $testEmail . "<br>";
        } else {
            echo "Failed to send test email.<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?> 