<?php
// Include database connection
require_once 'config/database.php';

// Set headers
header('Content-Type: text/html; charset=utf-8');

// Default assessment ID
$assessment_id = isset($_GET['id']) ? intval($_GET['id']) : 18;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        h1, h2 {
            color: #333;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Submission Test</h1>
    
    <form action="" method="get">
        <label for="id">Assessment ID:</label>
        <input type="number" name="id" id="id" value="<?php echo $assessment_id; ?>">
        <button type="submit">Load</button>
    </form>
    
    <h2>Assessment Details</h2>
    <?php
    try {
        // Get assessment details
        $assessment_query = "SELECT a.*, c.class_name 
                            FROM assessments a 
                            JOIN classes c ON a.class_id = c.id 
                            WHERE a.id = ?";
        
        $assessment_stmt = $conn->prepare($assessment_query);
        $assessment_stmt->bind_param("i", $assessment_id);
        $assessment_stmt->execute();
        $assessment_result = $assessment_stmt->get_result();
        
        if ($assessment_result->num_rows === 0) {
            echo "<p class='error'>Assessment not found</p>";
        } else {
            $assessment = $assessment_result->fetch_assoc();
            ?>
            <table>
                <tr>
                    <th>ID</th>
                    <td><?php echo $assessment['id']; ?></td>
                </tr>
                <tr>
                    <th>Title</th>
                    <td><?php echo htmlspecialchars($assessment['title']); ?></td>
                </tr>
                <tr>
                    <th>Class</th>
                    <td><?php echo htmlspecialchars($assessment['class_name']); ?></td>
                </tr>
                <tr>
                    <th>Due Date</th>
                    <td><?php echo $assessment['due_date']; ?></td>
                </tr>
                <tr>
                    <th>Max Score</th>
                    <td><?php echo $assessment['max_score']; ?></td>
                </tr>
            </table>
            
            <h2>Submissions</h2>
            <?php
            // Get submissions for this assessment
            $submissions_query = "SELECT ss.*, s.full_name as student_name, s.email as student_email, s.student_id as student_id_number
                                FROM student_submissions ss
                                JOIN students s ON ss.student_id = s.id
                                WHERE ss.assessment_id = ?";
            
            $submissions_stmt = $conn->prepare($submissions_query);
            $submissions_stmt->bind_param("i", $assessment_id);
            $submissions_stmt->execute();
            $submissions_result = $submissions_stmt->get_result();
            
            if ($submissions_result->num_rows === 0) {
                echo "<p>No submissions found for this assessment.</p>";
            } else {
                echo "<table>";
                echo "<tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Student ID</th>
                        <th>Date</th>
                        <th>Score</th>
                        <th>Files</th>
                    </tr>";
                
                while ($submission = $submissions_result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $submission['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($submission['student_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($submission['student_email']) . "</td>";
                    echo "<td>" . htmlspecialchars($submission['student_id_number']) . "</td>";
                    echo "<td>" . $submission['submission_date'] . "</td>";
                    echo "<td>" . ($submission['score'] !== null ? $submission['score'] : 'Not graded') . "</td>";
                    
                    // Get files for this submission
                    $files_query = "SELECT * FROM submission_files WHERE submission_id = ?";
                    $files_stmt = $conn->prepare($files_query);
                    $files_stmt->bind_param("i", $submission['id']);
                    $files_stmt->execute();
                    $files_result = $files_stmt->get_result();
                    
                    echo "<td>";
                    if ($files_result->num_rows === 0) {
                        echo "No files";
                    } else {
                        echo "<ul>";
                        while ($file = $files_result->fetch_assoc()) {
                            echo "<li>" . htmlspecialchars($file['file_name']) . " (" . $file['file_type'] . ")</li>";
                        }
                        echo "</ul>";
                    }
                    echo "</td>";
                    
                    echo "</tr>";
                }
                
                echo "</table>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>
</body>
</html> 