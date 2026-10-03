<?php
/**
 * Class View Logs Viewer
 * 
 * This page allows administrators to view and analyze class view logs.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db_connect.php';

// Check if user is logged in as an admin
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['teacher_id'])) {
    header('Location: admin-login.php');
    exit;
}

// Default filter values
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;

// Build query based on filters
$query = "
    SELECT 
        cvl.id, 
        cvl.student_id, 
        s.full_name AS student_name, 
        cvl.class_id, 
        c.class_name, 
        cvl.view_datetime, 
        cvl.view_duration, 
        cvl.ip_address, 
        cvl.device_info
    FROM 
        class_view_logs cvl
    JOIN 
        students s ON cvl.student_id = s.id
    JOIN 
        classes c ON cvl.class_id = c.id
    WHERE 
        1=1
";

$params = [];

// Add filters to query
if ($student_id) {
    $query .= " AND cvl.student_id = ?";
    $params[] = $student_id;
}

if ($class_id) {
    $query .= " AND cvl.class_id = ?";
    $params[] = $class_id;
}

if ($date_from) {
    $query .= " AND DATE(cvl.view_datetime) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(cvl.view_datetime) <= ?";
    $params[] = $date_to;
}

// Add sorting and limit - directly include the integer in the query
$query .= " ORDER BY cvl.view_datetime DESC LIMIT " . intval($limit);

// Get logs
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get students for filter dropdown
    $stmt = $pdo->query("SELECT id, full_name FROM students ORDER BY full_name");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get classes for filter dropdown
    $stmt = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class View Logs - StudyHub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --primary-light: #eaf2fd;
            --secondary-color: #2c3e50;
            --accent-color: #1abc9c;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --success-color: #2ecc71;
            --light-gray: #f5f7fa;
            --medium-gray: #ecf0f1;
            --dark-gray: #7f8c8d;
            --text-color: #34495e;
            --border-color: #e0e0e0;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-gray);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .back-link {
            margin-bottom: 20px;
        }
        
        .back-link a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.2s ease;
            padding: 8px 0;
        }
        
        .back-link a:hover {
            color: var(--primary-dark);
            transform: translateX(-3px);
        }
        
        .back-link a i {
            font-size: 14px;
        }
        
        header {
            background-color: var(--primary-color);
            background-image: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 25px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 0 0 8px 8px;
            box-shadow: var(--box-shadow);
        }
        
        h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .filters {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--secondary-color);
            font-size: 14px;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .logs-table {
            width: 100%;
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--box-shadow);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }
        
        th {
            background-color: var(--primary-light);
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        tr:hover {
            background-color: var(--light-gray);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--dark-gray);
            font-style: italic;
        }
        
        .summary {
            margin-top: 30px;
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: var(--box-shadow);
            border: 1px solid var(--border-color);
        }
        
        .summary h2 {
            margin-bottom: 20px;
            color: var(--secondary-color);
            font-weight: 600;
            font-size: 1.5rem;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .summary-card {
            background-color: var(--light-gray);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .summary-card h3 {
            font-size: 1rem;
            margin-bottom: 10px;
            color: var(--secondary-color);
            font-weight: 600;
        }
        
        .summary-card p {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .export-btn {
            background-color: var(--accent-color);
        }
        
        .export-btn:hover {
            background-color: #16a085;
        }
        
        .reset-btn {
            background-color: var(--dark-gray);
        }
        
        .reset-btn:hover {
            background-color: #6c7a89;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 15px;
        }
        
        .error {
            background-color: #fdecea;
            color: var(--danger-color);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid var(--danger-color);
            font-size: 14px;
        }
        
        .pdf-btn {
            background-color: #e74c3c;
        }
        
        .pdf-btn:hover {
            background-color: #c0392b;
        }
        
        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
                gap: 15px;
            }
            
            .form-group {
                width: 100%;
            }
            
            .summary-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
            
            button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-chart-line"></i> Class View Logs</h1>
        <p>View and analyze student class viewing activity</p>
    </header>
    
    <div class="container">
        <div class="back-link">
            <a href="teacher-dashboard.html"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="filters">
            <form class="filter-form" method="GET">
                <div class="form-group">
                    <label for="student_id"><i class="fas fa-user-graduate"></i> Student</label>
                    <select name="student_id" id="student_id">
                        <option value="">All Students</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>" <?php echo $student_id == $student['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="class_id"><i class="fas fa-chalkboard"></i> Class</label>
                    <select name="class_id" id="class_id">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo $class_id == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_from"><i class="fas fa-calendar-alt"></i> From Date</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>">
                </div>
                
                <div class="form-group">
                    <label for="date_to"><i class="fas fa-calendar-alt"></i> To Date</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>">
                </div>
                
                <div class="form-group">
                    <label for="limit"><i class="fas fa-list-ol"></i> Limit</label>
                    <select name="limit" id="limit">
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 records</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100 records</option>
                        <option value="250" <?php echo $limit == 250 ? 'selected' : ''; ?>>250 records</option>
                        <option value="500" <?php echo $limit == 500 ? 'selected' : ''; ?>>500 records</option>
                    </select>
                </div>
                
                <div class="actions">
                    <button type="submit"><i class="fas fa-filter"></i> Apply Filters</button>
                    <button type="button" class="reset-btn" onclick="window.location.href='view_class_logs.php'"><i class="fas fa-undo"></i> Reset</button>
                    <button type="button" class="export-btn pdf-btn" onclick="exportToPDF()"><i class="fas fa-file-pdf"></i> Export PDF</button>
                </div>
            </form>
        </div>
        
        <div class="logs-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><i class="fas fa-user-graduate"></i> Student</th>
                        <th><i class="fas fa-chalkboard"></i> Class</th>
                        <th><i class="fas fa-clock"></i> View Date/Time</th>
                        <th><i class="fas fa-stopwatch"></i> Duration</th>
                        <th><i class="fas fa-network-wired"></i> IP Address</th>
                        <th><i class="fas fa-laptop"></i> Device Info</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="no-data"><i class="fas fa-info-circle"></i> No logs found matching your criteria</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td><?php echo htmlspecialchars($log['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($log['class_name']); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($log['view_datetime'])); ?></td>
                                <td>
                                    <?php 
                                    if ($log['view_duration'] !== null) {
                                        // Format seconds into minutes and seconds
                                        $minutes = floor($log['view_duration'] / 60);
                                        $seconds = $log['view_duration'] % 60;
                                        echo $minutes . 'm ' . $seconds . 's';
                                    } else {
                                        echo '<span style="color:var(--dark-gray);">Not recorded</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td><?php echo htmlspecialchars(substr($log['device_info'], 0, 50)) . (strlen($log['device_info']) > 50 ? '...' : ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($logs)): ?>
            <div class="summary">
                <h2><i class="fas fa-chart-pie"></i> Summary Statistics</h2>
                <div class="summary-grid">
                    <?php
                    // Calculate summary statistics
                    $totalViews = count($logs);
                    
                    // Calculate total duration
                    $totalDuration = 0;
                    $viewsWithDuration = 0;
                    foreach ($logs as $log) {
                        if ($log['view_duration'] !== null) {
                            $totalDuration += $log['view_duration'];
                            $viewsWithDuration++;
                        }
                    }
                    
                    // Calculate average duration
                    $avgDuration = $viewsWithDuration > 0 ? $totalDuration / $viewsWithDuration : 0;
                    
                    // Format total duration
                    $totalHours = floor($totalDuration / 3600);
                    $totalMinutes = floor(($totalDuration % 3600) / 60);
                    $totalSeconds = $totalDuration % 60;
                    
                    // Format average duration
                    $avgMinutes = floor($avgDuration / 60);
                    $avgSeconds = round($avgDuration % 60);
                    
                    // Count unique students and classes
                    $uniqueStudents = [];
                    $uniqueClasses = [];
                    foreach ($logs as $log) {
                        $uniqueStudents[$log['student_id']] = true;
                        $uniqueClasses[$log['class_id']] = true;
                    }
                    ?>
                    
                    <div class="summary-card">
                        <h3><i class="fas fa-eye"></i> Total Views</h3>
                        <p><?php echo $totalViews; ?></p>
                    </div>
                    
                    <div class="summary-card">
                        <h3><i class="fas fa-users"></i> Unique Students</h3>
                        <p><?php echo count($uniqueStudents); ?></p>
                    </div>
                    
                    <div class="summary-card">
                        <h3><i class="fas fa-chalkboard-teacher"></i> Unique Classes</h3>
                        <p><?php echo count($uniqueClasses); ?></p>
                    </div>
                    
                    <div class="summary-card">
                        <h3><i class="fas fa-hourglass"></i> Total View Time</h3>
                        <p><?php echo $totalHours . 'h ' . $totalMinutes . 'm ' . $totalSeconds . 's'; ?></p>
                    </div>
                    
                    <div class="summary-card">
                        <h3><i class="fas fa-tachometer-alt"></i> Average View Duration</h3>
                        <p><?php echo $avgMinutes . 'm ' . $avgSeconds . 's'; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function exportToPDF() {
            // Get current URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            
            // Create export URL
            const exportUrl = 'export_class_logs.php?' + urlParams.toString();
            
            // Open in new tab
            window.open(exportUrl, '_blank');
        }
    </script>
</body>
</html> 