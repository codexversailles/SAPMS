<?php
// Include database connection
require_once 'config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Output as HTML for readability
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Creation Debug</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1, h2 {
            color: #2c3e50;
        }
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow: auto;
        }
        .success {
            color: #2ecc71;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
        .warning {
            color: #f39c12;
            font-weight: bold;
        }
        .info {
            color: #3498db;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #2980b9;
        }
        input, select {
            padding: 8px;
            margin-bottom: 10px;
            width: 100%;
            max-width: 300px;
        }
    </style>
</head>
<body>
    <h1>Class Creation Debug Tool</h1>
    
    <div class="test-section">
        <h2>Session Information</h2>
        <?php
        // Display session information
        echo "<p>Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "<span class='success'>Active</span>" : "<span class='error'>Inactive</span>") . "</p>";
        echo "<p>Teacher ID in Session: " . (isset($_SESSION['teacher_id']) ? "<span class='success'>{$_SESSION['teacher_id']}</span>" : "<span class='error'>Not set</span>") . "</p>";
        
        if (!isset($_SESSION['teacher_id'])) {
            echo "<p class='warning'>Warning: No teacher ID found in session. This will prevent class creation.</p>";
            echo "<form method='post' action=''>";
            echo "<h3>Set Test Teacher ID</h3>";
            echo "<p>Select a teacher to use for testing:</p>";
            
            // Get available teachers
            $teachers_query = "SELECT id, full_name, email FROM teachers ORDER BY id ASC";
            $teachers_result = $conn->query($teachers_query);
            
            if ($teachers_result && $teachers_result->num_rows > 0) {
                echo "<select name='teacher_id'>";
                while ($teacher = $teachers_result->fetch_assoc()) {
                    echo "<option value='{$teacher['id']}'>{$teacher['full_name']} ({$teacher['email']}) - ID: {$teacher['id']}</option>";
                }
                echo "</select>";
                echo "<button type='submit' name='set_teacher'>Set Test Teacher</button>";
            } else {
                echo "<p class='error'>No teachers found in the database.</p>";
            }
            echo "</form>";
        }
        
        // Handle setting test teacher
        if (isset($_POST['set_teacher']) && isset($_POST['teacher_id'])) {
            $_SESSION['teacher_id'] = intval($_POST['teacher_id']);
            echo "<p class='success'>Teacher ID set to {$_SESSION['teacher_id']} for testing.</p>";
            echo "<script>window.location.reload();</script>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Database Connection</h2>
        <?php
        // Test database connection
        if ($conn->connect_error) {
            echo "<p class='error'>Connection failed: " . $conn->connect_error . "</p>";
        } else {
            echo "<p class='success'>Database connection successful!</p>";
            
            // Display database info
            $result = $conn->query("SELECT DATABASE() as db_name");
            $row = $result->fetch_assoc();
            echo "<p>Connected to database: <strong>{$row['db_name']}</strong></p>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Classes Table</h2>
        <?php
        // Check classes table
        $result = $conn->query("SHOW TABLES LIKE 'classes'");
        if ($result->num_rows > 0) {
            echo "<p class='success'>Classes table exists!</p>";
            
            // Show table structure
            $structure = $conn->query("DESCRIBE classes");
            if ($structure) {
                echo "<h3>Table Structure:</h3>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                while ($row = $structure->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$row['Field']}</td>";
                    echo "<td>{$row['Type']}</td>";
                    echo "<td>{$row['Null']}</td>";
                    echo "<td>{$row['Key']}</td>";
                    echo "<td>{$row['Default']}</td>";
                    echo "<td>{$row['Extra']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Show existing classes (limited to 10)
            $classes = $conn->query("SELECT * FROM classes ORDER BY id DESC LIMIT 10");
            if ($classes && $classes->num_rows > 0) {
                echo "<h3>Recent Classes:</h3>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Name</th><th>Code</th><th>Teacher ID</th><th>Created</th></tr>";
                while ($class = $classes->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$class['id']}</td>";
                    echo "<td>{$class['class_name']}</td>";
                    echo "<td>{$class['class_code']}</td>";
                    echo "<td>{$class['teacher_id']}</td>";
                    echo "<td>{$class['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='info'>No classes found in the database.</p>";
            }
        } else {
            echo "<p class='error'>Classes table does not exist!</p>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Test Class Creation</h2>
        <?php if (isset($_SESSION['teacher_id'])): ?>
        <form method="post" action="">
            <div>
                <label for="class_name">Class Name:</label>
                <input type="text" id="class_name" name="class_name" required>
            </div>
            <div>
                <label for="class_code">Class Code:</label>
                <input type="text" id="class_code" name="class_code" value="<?php echo generateRandomCode(); ?>" required>
                <p><small>This is a random 6-character code. You can change it if needed.</small></p>
            </div>
            <button type="submit" name="test_create">Test Create Class</button>
        </form>
        
        <?php 
        // Handle test class creation
        if (isset($_POST['test_create']) && isset($_POST['class_name']) && isset($_POST['class_code'])) {
            $class_name = $_POST['class_name'];
            $class_code = $_POST['class_code'];
            $teacher_id = $_SESSION['teacher_id'];
            
            echo "<h3>Test Results:</h3>";
            
            // Validate input
            $validation_errors = [];
            if (empty($class_name)) {
                $validation_errors[] = "Class name cannot be empty";
            }
            if (empty($class_code)) {
                $validation_errors[] = "Class code cannot be empty";
            }
            if (!preg_match('/^[A-Z0-9]{6}$/', $class_code)) {
                $validation_errors[] = "Class code must be 6 alphanumeric characters (A-Z, 0-9)";
            }
            
            if (!empty($validation_errors)) {
                echo "<p class='error'>Validation errors:</p>";
                echo "<ul>";
                foreach ($validation_errors as $error) {
                    echo "<li>{$error}</li>";
                }
                echo "</ul>";
            } else {
                // Check if class code already exists
                $check_stmt = $conn->prepare("SELECT id FROM classes WHERE class_code = ?");
                $check_stmt->bind_param("s", $class_code);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    echo "<p class='error'>Class code '{$class_code}' already exists in the database.</p>";
                } else {
                    // Try to insert the class
                    try {
                        $insert_stmt = $conn->prepare("INSERT INTO classes (class_name, class_code, teacher_id) VALUES (?, ?, ?)");
                        $insert_stmt->bind_param("ssi", $class_name, $class_code, $teacher_id);
                        $result = $insert_stmt->execute();
                        
                        if ($result) {
                            $class_id = $conn->insert_id;
                            echo "<p class='success'>Test class created successfully!</p>";
                            echo "<p>Class ID: {$class_id}</p>";
                            echo "<p>Class Name: {$class_name}</p>";
                            echo "<p>Class Code: {$class_code}</p>";
                            echo "<p>Teacher ID: {$teacher_id}</p>";
                        } else {
                            echo "<p class='error'>Failed to create class: " . $conn->error . "</p>";
                        }
                    } catch (Exception $e) {
                        echo "<p class='error'>Error creating class: " . $e->getMessage() . "</p>";
                    }
                }
            }
        }
        ?>
        <?php else: ?>
            <p class='error'>You need to set a teacher ID first before you can test class creation.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Helper function to generate a random class code
function generateRandomCode() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}
?> 