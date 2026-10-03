<?php
/**
 * Class Logs Export Script
 * 
 * This script exports class view logs to PDF format.
 */

// Start output buffering to prevent any accidental output before PDF generation
ob_start();

// Disable error reporting for output
error_reporting(0);
ini_set('display_errors', 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db_connect.php';

// Check if user is logged in as an admin or teacher
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['teacher_id'])) {
    header('Location: admin-login.php');
    exit;
}

// Get filter parameters
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

// Add sorting by date
$query .= " ORDER BY cvl.view_datetime DESC";

// Get logs
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format file name with date
    $date = date('Y-m-d_H-i-s');
    $fileName = "SAP-MS_class_logs_" . $date;
    
    // Calculate summary statistics for PDF
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
    $totalDurationFormatted = $totalHours . 'h ' . $totalMinutes . 'm ' . $totalSeconds . 's';
    
    // Format average duration
    $avgMinutes = floor($avgDuration / 60);
    $avgSeconds = round($avgDuration % 60);
    $avgDurationFormatted = $avgMinutes . 'm ' . $avgSeconds . 's';
    
    // Count unique students and classes
    $uniqueStudents = [];
    $uniqueClasses = [];
    foreach ($logs as $log) {
        $uniqueStudents[$log['student_id']] = true;
        $uniqueClasses[$log['class_id']] = true;
    }
    $uniqueStudentCount = count($uniqueStudents);
    $uniqueClassCount = count($uniqueClasses);
    
    // PDF Export using TCPDF
    
    // Include TCPDF library
    require_once('tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('SAP-MS');
    $pdf->SetAuthor('SAP-MS System');
    $pdf->SetTitle('Class View Logs Report');
    $pdf->SetSubject('Class View Logs');
    $pdf->SetKeywords('SAP-MS, Logs, Class, View, Report');
    
    // Set default header data
    $pdf->SetHeaderData('', 0, 'SAP-MS - Class View Logs', 'Generated on: ' . date('Y-m-d H:i:s'), array(0,64,255), array(0,64,128));
    $pdf->setFooterData(array(0,64,0), array(0,64,128));
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array('helvetica', '', 10));
    $pdf->setFooterFont(Array('helvetica', '', 8));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Set image scale factor
    $pdf->setImageScale(1.25);
    
    // Set default font subsetting mode
    $pdf->setFontSubsetting(true);
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Add a page
    $pdf->AddPage();
    
    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'SAP-MS Class View Logs Report', 0, 1, 'C');
    $pdf->Ln(2);
    
    // Filter information
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Date Range: ' . $date_from . ' to ' . $date_to, 0, 1, 'L');
    if ($student_id) {
        // Get student name
        $stmt = $pdo->prepare("SELECT full_name FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $studentName = $stmt->fetchColumn();
        $pdf->Cell(0, 6, 'Student: ' . $studentName, 0, 1, 'L');
    }
    if ($class_id) {
        // Get class name
        $stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $className = $stmt->fetchColumn();
        $pdf->Cell(0, 6, 'Class: ' . $className, 0, 1, 'L');
    }
    $pdf->Ln(5);
    
    // Summary Statistics
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Summary Statistics', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetFillColor(240, 240, 240);
    
    // Create a statistics table
    $statsWidth = 180; // Total width for stats section
    $colWidth = $statsWidth / 3; // 3 columns
    
    // First row
    $pdf->Cell($colWidth, 10, 'Total Views: ' . $totalViews, 1, 0, 'L', 1);
    $pdf->Cell($colWidth, 10, 'Unique Students: ' . $uniqueStudentCount, 1, 0, 'L', 1);
    $pdf->Cell($colWidth, 10, 'Unique Classes: ' . $uniqueClassCount, 1, 1, 'L', 1);
    
    // Second row
    $pdf->Cell($colWidth, 10, 'Total View Time: ' . $totalDurationFormatted, 1, 0, 'L', 1);
    $pdf->Cell($colWidth, 10, 'Average View Duration: ' . $avgDurationFormatted, 1, 0, 'L', 1);
    $pdf->Cell($colWidth, 10, 'Date Generated: ' . date('Y-m-d'), 1, 1, 'L', 1);
    
    $pdf->Ln(5);
    
    // Logs Table
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Detailed Logs', 0, 1, 'L');
    
    // Table header
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(52, 152, 219); // Blue header
    $pdf->SetTextColor(255);
    
    // Define column widths (total should be <= 270 for landscape A4)
    $colWidths = array(10, 40, 40, 40, 25, 30, 75);
    
    // Header row
    $pdf->Cell($colWidths[0], 8, 'ID', 1, 0, 'C', 1);
    $pdf->Cell($colWidths[1], 8, 'Student', 1, 0, 'C', 1);
    $pdf->Cell($colWidths[2], 8, 'Class', 1, 0, 'C', 1);
    $pdf->Cell($colWidths[3], 8, 'View Date/Time', 1, 0, 'C', 1);
    $pdf->Cell($colWidths[4], 8, 'Duration', 1, 0, 'C', 1);
    $pdf->Cell($colWidths[5], 8, 'IP Address', 1, 0, 'C', 1);
    $pdf->Cell($colWidths[6], 8, 'Device Info', 1, 1, 'C', 1);
    
    // Table rows
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0);
    $pdf->SetFillColor(245, 247, 250); // Light blue for alternating rows
    
    $fillRow = false;
    $rowCounter = 0;
    $maxRowsPerPage = 20; // Adjust based on your design
    
    foreach ($logs as $log) {
        // Check if we need a new page
        if ($rowCounter >= $maxRowsPerPage) {
            $pdf->AddPage();
            
            // Repeat table header on new page
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(52, 152, 219);
            $pdf->SetTextColor(255);
            
            $pdf->Cell($colWidths[0], 8, 'ID', 1, 0, 'C', 1);
            $pdf->Cell($colWidths[1], 8, 'Student', 1, 0, 'C', 1);
            $pdf->Cell($colWidths[2], 8, 'Class', 1, 0, 'C', 1);
            $pdf->Cell($colWidths[3], 8, 'View Date/Time', 1, 0, 'C', 1);
            $pdf->Cell($colWidths[4], 8, 'Duration', 1, 0, 'C', 1);
            $pdf->Cell($colWidths[5], 8, 'IP Address', 1, 0, 'C', 1);
            $pdf->Cell($colWidths[6], 8, 'Device Info', 1, 1, 'C', 1);
            
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(0);
            $rowCounter = 0;
        }
        
        // Format duration
        if ($log['view_duration'] !== null) {
            $minutes = floor($log['view_duration'] / 60);
            $seconds = $log['view_duration'] % 60;
            $duration = $minutes . 'm ' . $seconds . 's';
        } else {
            $duration = 'Not recorded';
        }
        
        // Truncate device info
        $deviceInfo = substr($log['device_info'], 0, 50) . (strlen($log['device_info']) > 50 ? '...' : '');
        
        // Format datetime
        $viewDateTime = date('Y-m-d H:i:s', strtotime($log['view_datetime']));
        
        // Print row
        $pdf->Cell($colWidths[0], 7, $log['id'], 1, 0, 'C', $fillRow);
        $pdf->Cell($colWidths[1], 7, $log['student_name'], 1, 0, 'L', $fillRow);
        $pdf->Cell($colWidths[2], 7, $log['class_name'], 1, 0, 'L', $fillRow);
        $pdf->Cell($colWidths[3], 7, $viewDateTime, 1, 0, 'C', $fillRow);
        $pdf->Cell($colWidths[4], 7, $duration, 1, 0, 'C', $fillRow);
        $pdf->Cell($colWidths[5], 7, $log['ip_address'], 1, 0, 'C', $fillRow);
        $pdf->Cell($colWidths[6], 7, $deviceInfo, 1, 1, 'L', $fillRow);
        
        $fillRow = !$fillRow; // Alternate row colors
        $rowCounter++;
    }
    
    // Clean any previous output to avoid "already sent" errors
    if (ob_get_contents()) ob_clean();
    
    // Force clean any previous output
    ob_end_clean();
    
    // Send PDF to browser
    $pdf->Output($fileName . '.pdf', 'I');
    exit();
    
} catch (PDOException $e) {
    // Clean output buffer
    if (ob_get_contents()) ob_clean();
    
    // Handle error - output plain text for both formats
    header('Content-Type: text/plain');
    echo "Error generating export: " . $e->getMessage();
    exit();
}
// No closing PHP tag to prevent accidental whitespace 