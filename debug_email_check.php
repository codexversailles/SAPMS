<?php
// Simple debugging script to check PHPMailer installation
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>PHPMailer Check</h1>";

// Check if files exist
$files = [
    'PHPMailer/src/PHPMailer.php',
    'PHPMailer/src/SMTP.php',
    'PHPMailer/src/Exception.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ {$file} exists<br>";
    } else {
        echo "✗ {$file} does not exist<br>";
    }
}

// Try to include the files to see if they load properly
echo "<h2>Testing PHPMailer includes</h2>";
try {
    require 'PHPMailer/src/Exception.php';
    echo "✓ Exception.php included successfully<br>";
    
    require 'PHPMailer/src/PHPMailer.php';
    echo "✓ PHPMailer.php included successfully<br>";
    
    require 'PHPMailer/src/SMTP.php';
    echo "✓ SMTP.php included successfully<br>";
    
    echo "<h2>Testing class existence</h2>";
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "✓ PHPMailer class exists<br>";
    } else {
        echo "✗ PHPMailer class does not exist<br>";
    }
    
    if (class_exists('PHPMailer\\PHPMailer\\SMTP')) {
        echo "✓ SMTP class exists<br>";
    } else {
        echo "✗ SMTP class does not exist<br>";
    }
    
    if (class_exists('PHPMailer\\PHPMailer\\Exception')) {
        echo "✓ Exception class exists<br>";
    } else {
        echo "✗ Exception class does not exist<br>";
    }
    
    // Create PHPMailer instance
    echo "<h2>Testing PHPMailer instance creation</h2>";
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    echo "✓ PHPMailer instance created successfully<br>";
    
    echo "<h2>Testing SMTP settings</h2>";
    echo "<form method='post'>";
    echo "<p>Gmail username: <input type='email' name='email' required></p>";
    echo "<p>App password: <input type='password' name='password' required></p>";
    echo "<p>Test recipient: <input type='email' name='recipient' required></p>";
    echo "<button type='submit'>Test Email Connection</button>";
    echo "</form>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'], $_POST['recipient'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $recipient = $_POST['recipient'];
        
        echo "<h3>Testing SMTP connection to Gmail</h3>";
        
        try {
            // Server settings
            $mail->SMTPDebug = 2; // Enable verbose debug output
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $email;
            $mail->Password = $password;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            
            // Recipients
            $mail->setFrom($email, 'StudyHub Test');
            $mail->addAddress($recipient);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'StudyHub PHPMailer Test';
            $mail->Body = "This is a test email from StudyHub.";
            
            $mail->send();
            echo "✓ Email sent successfully<br>";
        } catch (Exception $e) {
            echo "✗ Email could not be sent: " . $mail->ErrorInfo . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?> 