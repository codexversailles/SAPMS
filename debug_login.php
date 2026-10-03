<?php
// Include database connection
require_once 'config/database.php';

// Set headers
header('Content-Type: text/html');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['teacher_id'])) {
    $teacher_id = intval($_POST['teacher_id']);
    
    // Validate teacher exists
    $check_query = "SELECT id, full_name, email FROM teachers WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $teacher_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $teacher = $check_result->fetch_assoc();
        
        // Set session variables
        $_SESSION['teacher_id'] = $teacher_id;
        $_SESSION['teacher_name'] = $teacher['full_name'];
        $_SESSION['teacher_email'] = $teacher['email'];
        
        $success_message = "Successfully logged in as teacher: {$teacher['full_name']} (ID: {$teacher_id})";
    } else {
        $error_message = "Teacher with ID {$teacher_id} not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Teacher Login</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .success { color: green; padding: 10px; background-color: #e7f7e7; border: 1px solid green; margin: 10px 0; }
        .error { color: red; padding: 10px; background-color: #f7e7e7; border: 1px solid red; margin: 10px 0; }
        form { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        select, button { padding: 8px; margin: 5px 0; }
        .session-info { background-color: #f5f5f5; padding: 15px; margin-top: 20px; border: 1px solid #ddd; }
        .links { margin-top: 20px; }
        .links a { display: block; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Debug Teacher Login</h1>
    
    <?php if (isset($success_message)): ?>
        <div class="success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <form method="post">
        <h2>Login as Teacher</h2>
        <div>
            <label for="teacher_id">Select Teacher:</label>
            <select name="teacher_id" id="teacher_id">
                <?php
                // Get all teachers
                $teachers_query = "SELECT id, full_name, email FROM teachers ORDER BY id ASC";
                $teachers_result = $conn->query($teachers_query);
                
                if ($teachers_result && $teachers_result->num_rows > 0) {
                    while ($teacher = $teachers_result->fetch_assoc()) {
                        $selected = (isset($_SESSION['teacher_id']) && $_SESSION['teacher_id'] == $teacher['id']) ? 'selected' : '';
                        echo "<option value='{$teacher['id']}' {$selected}>{$teacher['full_name']} ({$teacher['email']}) - ID: {$teacher['id']}</option>";
                    }
                } else {
                    echo "<option value=''>No teachers found</option>";
                }
                ?>
            </select>
        </div>
        <button type="submit">Login as this Teacher</button>
    </form>
    
    <div class="session-info">
        <h2>Current Session Info:</h2>
        <?php if (session_status() === PHP_SESSION_ACTIVE): ?>
            <p>Session ID: <?php echo session_id(); ?></p>
            <h3>Session Variables:</h3>
            <pre><?php print_r($_SESSION); ?></pre>
        <?php else: ?>
            <p>No active session.</p>
        <?php endif; ?>
    </div>
    
    <div class="links">
        <a href="check_teacher_session.php" target="_blank">View Session JSON</a>
        <a href="teacher-dashboard.html">Go to Teacher Dashboard</a>
        <?php if (isset($_SESSION['teacher_id'])): ?>
            <a href="logout.php">Logout</a>
        <?php endif; ?>
    </div>
</body>
</html> 