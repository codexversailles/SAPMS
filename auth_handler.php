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

class AuthHandler {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function register($full_name, $email, $password) {
        try {
            // Validate input
            if (empty($full_name) || empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'All fields are required'];
            }

            if (strlen($password) < PASSWORD_MIN_LENGTH) {
                return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'];
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }
            
            // Check if email already exists
            $stmt = $this->pdo->prepare("SELECT id FROM students WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email already registered'];
            }
            
            // Check if email verification is complete
            if (!isset($_SESSION['email_verified']) || $_SESSION['email_verified'] !== true || 
                !isset($_SESSION['verification_email']) || $_SESSION['verification_email'] !== $email) {
                return ['success' => false, 'message' => 'Email verification is required'];
            }
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new student
            $stmt = $this->pdo->prepare("INSERT INTO students (full_name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$full_name, $email, $hashed_password]);
            
            // Clear verification session data
            unset($_SESSION['email_verified']);
            unset($_SESSION['verification_email']);
            
            return ['success' => true, 'message' => 'Registration successful'];
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please check if the database is properly set up.'];
        }
    }
    
    public function login($email, $password, $remember_me = false) {
        try {
            // Check for too many login attempts
            if ($this->is_login_blocked($email)) {
                return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
            }
            
            // Removed email verification requirement for login
            
            // Get student
            $stmt = $this->pdo->prepare("SELECT id, password FROM students WHERE email = ? AND is_active = TRUE");
            $stmt->execute([$email]);
            $student = $stmt->fetch();
            
            if (!$student || !password_verify($password, $student['password'])) {
                $this->record_login_attempt($email);
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Clear login attempts
            $this->clear_login_attempts($email);
            
            // Update last login
            $stmt = $this->pdo->prepare("UPDATE students SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$student['id']]);
            
            // Set session
            $_SESSION['student_id'] = $student['id'];
            
            // Clear verification session data if any
            if(isset($_SESSION['email_verified'])) unset($_SESSION['email_verified']);
            if(isset($_SESSION['verification_email'])) unset($_SESSION['verification_email']);
            
            // Handle remember me
            if ($remember_me) {
                $token = generate_token();
                $expires = date('Y-m-d H:i:s', time() + COOKIE_LIFETIME);
                
                $stmt = $this->pdo->prepare("INSERT INTO sessions (student_id, session_token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$student['id'], $token, $expires]);
                
                setcookie('remember_token', $token, time() + COOKIE_LIFETIME, '/', '', true, true);
            }
            
            return ['success' => true, 'message' => 'Login successful'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }
    
    public function verify_credentials($email, $password) {
        try {
            // Check for too many login attempts
            if ($this->is_login_blocked($email)) {
                return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
            }
            
            // Get student
            $stmt = $this->pdo->prepare("SELECT id, password FROM students WHERE email = ? AND is_active = TRUE");
            $stmt->execute([$email]);
            $student = $stmt->fetch();
            
            if (!$student || !password_verify($password, $student['password'])) {
                $this->record_login_attempt($email);
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            return ['success' => true, 'message' => 'Credentials valid'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()];
        }
    }
    
    private function is_login_blocked($email) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE email = ? 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$email, LOGIN_TIMEOUT]);
        $result = $stmt->fetch();
        
        return $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
    }
    
    private function record_login_attempt($email) {
        $stmt = $this->pdo->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
        $stmt->execute([$email, $_SERVER['REMOTE_ADDR']]);
    }
    
    private function clear_login_attempts($email) {
        $stmt = $this->pdo->prepare("DELETE FROM login_attempts WHERE email = ?");
        $stmt->execute([$email]);
    }
    
    public function logout() {
        // Clear session
        session_unset();
        session_destroy();
        
        // Clear remember me cookie if exists
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE session_token = ?");
            $stmt->execute([$token]);
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        return ['success' => true, 'message' => 'Logged out successfully'];
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
        error_log("Received auth request: " . json_encode($_POST));
        
        // Create PDO connection
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

    $auth = new AuthHandler($pdo);
        
        if (!isset($_POST['action'])) {
            sendJsonResponse(['success' => false, 'message' => 'Missing action parameter'], 400);
        }
    
        switch ($_POST['action']) {
            case 'register':
                if (isset($_POST['full_name'], $_POST['email'], $_POST['password'])) {
                    $result = $auth->register(
                        sanitize_input($_POST['full_name']),
                        sanitize_input($_POST['email']),
                        $_POST['password']
                    );
                    sendJsonResponse($result);
                    } else {
                    sendJsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
                }
                break;
                
            case 'login':
                if (isset($_POST['email'], $_POST['password'])) {
                    $remember_me = isset($_POST['remember_me']) ? true : false;
                    $result = $auth->login(
                        sanitize_input($_POST['email']),
                        $_POST['password'],
                        $remember_me
                    );
                    sendJsonResponse($result);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
                }
                break;
                
            case 'verify_credentials':
                if (isset($_POST['email'], $_POST['password'])) {
                    $result = $auth->verify_credentials(
                        sanitize_input($_POST['email']),
                        $_POST['password']
                    );
                    sendJsonResponse($result);
                    } else {
                    sendJsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
                }
                break;
                
            case 'logout':
                $result = $auth->logout();
                sendJsonResponse($result);
                break;
                
            default:
                sendJsonResponse(['success' => false, 'message' => 'Unknown action: ' . $_POST['action']], 400);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'Database connection error. Please try again later.'], 500);
    } catch (Exception $e) {
        error_log("General error: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'An error occurred. Please try again.'], 500);
    }
}

// If script execution reaches here, return an error
sendJsonResponse(['success' => false, 'message' => 'Invalid request method or direct script access'], 400);
?>

// Helper function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?> 