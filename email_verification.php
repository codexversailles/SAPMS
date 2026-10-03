<?php
// Prevent any output before headers are sent
ob_start();

// Set error handling
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration
require_once 'config.php';

class EmailVerification {
    private $pdo;
    private $gmail_username;
    private $gmail_password;
    
    public function __construct($pdo, $gmail_username, $gmail_password) {
        $this->pdo = $pdo;
        $this->gmail_username = $gmail_username;
        $this->gmail_password = $gmail_password;
    }
    
    // Generate a random verification code
    public function generateVerificationCode() {
        return rand(100000, 999999); // 6-digit code
    }
    
    // Store verification code in the database
    public function storeVerificationCode($email, $code) {
        try {
            // First, remove any existing codes for this email
            $stmt = $this->pdo->prepare("DELETE FROM email_verification WHERE email = ?");
            $stmt->execute([$email]);
            
            // Then insert the new code with expiration (15 minutes from now)
            $stmt = $this->pdo->prepare("INSERT INTO email_verification (email, code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
            $stmt->execute([$email, $code]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error storing verification code: " . $e->getMessage());
            return false;
        }
    }
    
    // Send verification email using PHPMailer
    public function sendVerificationEmail($email, $code) {
        try {
            // Include PHPMailer
            $phpmailerPath = __DIR__ . '/PHPMailer/src/';
            require_once $phpmailerPath . 'Exception.php';
            require_once $phpmailerPath . 'PHPMailer.php';
            require_once $phpmailerPath . 'SMTP.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $this->gmail_username;
            $mail->Password = $this->gmail_password;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            
            // Recipients
            $mail->setFrom($this->gmail_username, 'SAPMS');
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'SAPMS - Email Verification Code';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;'>
                    <h2 style='color: #3498db;'>SAPMS Email Verification</h2>
                    <p>Your verification code is:</p>
                    <div style='background-color: #f8f9fa; padding: 10px; font-size: 24px; font-weight: bold; text-align: center; letter-spacing: 5px; margin: 20px 0;'>
                        {$code}
                    </div>
                    <p>This code will expire in 15 minutes.</p>
                    <p>If you didn't request this code, please ignore this email.</p>
                    <p style='font-size: 12px; color: #7f8c8d; margin-top: 30px;'>
                        This is an automated message, please do not reply.
                    </p>
                </div>
            ";
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    // Verify the code entered by the user
    public function verifyCode($email, $code) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM email_verification WHERE email = ? AND code = ? AND expires_at > NOW()");
            $stmt->execute([$email, $code]);
            
            if ($stmt->rowCount() > 0) {
                // Code is valid, delete it to prevent reuse
                $stmt = $this->pdo->prepare("DELETE FROM email_verification WHERE email = ?");
                $stmt->execute([$email]);
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error verifying code: " . $e->getMessage());
            return false;
        }
    }
}

// Function to send JSON response and exit
function sendJsonResponse($data, $statusCode = 200) {
    // Clean all output buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Set headers
    http_response_code($statusCode);
    header('Content-Type: application/json');
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    
    // Log the response being sent
    error_log("Sending JSON response: " . json_encode($data));
    
    // Output JSON and exit
    echo json_encode($data);
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Log received request
        error_log("Received request: " . json_encode($_POST));
        
        // Create PDO connection
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Gmail credentials - replace with your actual Gmail app credentials
        $gmail_username = 'lanceandrefermo@gmail.com';
        $gmail_password = 'oyoq yufr cesx kukb'; // Use Gmail App Password, not your actual Gmail password
        
        $emailVerification = new EmailVerification($pdo, $gmail_username, $gmail_password);
        $response = ['success' => false, 'message' => 'Invalid request'];
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'send_verification_code':
                    if (isset($_POST['email'])) {
                        $email = sanitize_input($_POST['email']);
                        
                        // Check if email exists (for login) or doesn't exist (for registration)
                        $isLogin = isset($_POST['is_login']) && $_POST['is_login'] === 'true';
                        $isParent = isset($_POST['is_parent']) && $_POST['is_parent'] === 'true';
                        
                        // Skip student email check for parents
                        if (!$isParent) {
                            $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
                            $stmt->execute([$email]);
                            $exists = $stmt->rowCount() > 0;
                            
                            if (($isLogin && !$exists) || (!$isLogin && $exists)) {
                                sendJsonResponse([
                                    'success' => false,
                                    'message' => $isLogin ? 'Email not found' : 'Email already registered'
                                ]);
                            }
                        } else {
                            // For parents, check if parent email already exists for new registrations
                            if (!$isLogin) {
                                $stmt = $pdo->prepare("SELECT id FROM parents WHERE email = ?");
                                $stmt->execute([$email]);
                                $exists = $stmt->rowCount() > 0;
                                
                                if ($exists) {
                                    sendJsonResponse([
                                        'success' => false,
                                        'message' => 'Email already registered'
                                    ]);
                                }
                            }
                        }
                        
                        $code = $emailVerification->generateVerificationCode();
                        
                        if ($emailVerification->storeVerificationCode($email, $code)) {
                            // Store email in session for verification
                            $_SESSION['verification_email'] = $email;
                            
                            if ($emailVerification->sendVerificationEmail($email, $code)) {
                                sendJsonResponse([
                                    'success' => true,
                                    'message' => 'Verification code sent to your email'
                                ]);
                            } else {
                                sendJsonResponse([
                                    'success' => false,
                                    'message' => 'Failed to send verification email. Please try again.'
                                ]);
                            }
                        } else {
                            sendJsonResponse([
                                'success' => false,
                                'message' => 'Failed to generate verification code. Please try again.'
                            ]);
                        }
                    } else {
                        sendJsonResponse([
                            'success' => false,
                            'message' => 'Email is required'
                        ]);
                    }
                    break;
                    
                case 'verify_code':
                    if (isset($_POST['code']) && isset($_SESSION['verification_email'])) {
                        $code = sanitize_input($_POST['code']);
                        $email = $_SESSION['verification_email'];
                        
                        error_log("Verifying code: $code for email: $email");
                        
                        if ($emailVerification->verifyCode($email, $code)) {
                            // Mark email as verified in session
                            $_SESSION['email_verified'] = true;
                            
                            sendJsonResponse([
                                'success' => true,
                                'message' => 'Email verification successful'
                            ]);
                        } else {
                            sendJsonResponse([
                                'success' => false,
                                'message' => 'Invalid or expired verification code'
                            ]);
                        }
                    } else {
                        sendJsonResponse([
                            'success' => false,
                            'message' => isset($_SESSION['verification_email']) ? 
                                'Verification code is required' : 
                                'Session expired. Please restart the verification process'
                        ]);
                    }
                    break;
                
                default:
                    sendJsonResponse([
                        'success' => false,
                        'message' => 'Unknown action: ' . $_POST['action']
                    ]);
            }
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Missing action parameter'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'message' => 'Database connection error. Please try again later.'
        ], 500);
    } catch (Exception $e) {
        error_log("General error: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ], 500);
    }
}

// If script execution reaches here, return an error
sendJsonResponse([
    'success' => false,
    'message' => 'Invalid request method or direct script access'
], 400);
?> 