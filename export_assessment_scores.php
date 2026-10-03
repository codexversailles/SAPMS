<?php
/**
 * Assessment Scores Export Script
 * 
 * This script exports assessment scores for a class to PDF format.
 * It includes both completed and missed assessments for all students.
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

// Get class ID from request
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;

if (!$class_id) {
    die('Error: No class ID provided');
}

try {
    // Get class information
    $stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        die('Error: Class not found');
    }
    
    // Get all assessments for this class
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            title, 
            description, 
            due_date, 
            max_score 
        FROM 
            assessments 
        WHERE 
            class_id = ? 
        ORDER BY 
            due_date DESC
    ");
    $stmt->execute([$class_id]);
    $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all students enrolled in this class
    $stmt = $pdo->prepare("
        SELECT 
            s.id, 
            s.student_id AS student_code, 
            s.full_name 
        FROM 
            students s
        JOIN 
            class_students cs ON s.id = cs.student_id 
        WHERE 
            cs.class_id = ? 
            AND cs.status = 'active' 
        ORDER BY 
            s.full_name
    ");
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create an array to store all score data
    $scoreData = [];
    
    // Initialize score data structure
    foreach ($students as $student) {
        $scoreData[$student['id']] = [
            'student_code' => $student['student_code'],
            'full_name' => $student['full_name'],
            'assessments' => []
        ];
    }
    
    // Get all submissions for these assessments
    foreach ($assessments as $assessment) {
        $stmt = $pdo->prepare("
            SELECT 
                student_id, 
                score, 
                submission_date
            FROM 
                student_submissions 
            WHERE 
                assessment_id = ?
        ");
        $stmt->execute([$assessment['id']]);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Map submissions to students
        $submissionsByStudent = [];
        foreach ($submissions as $submission) {
            $submissionsByStudent[$submission['student_id']] = $submission;
        }
        
        // Populate score data for each student
        foreach ($students as $student) {
            if (isset($submissionsByStudent[$student['id']])) {
                // Student has submitted
                $scoreData[$student['id']]['assessments'][$assessment['id']] = [
                    'title' => $assessment['title'],
                    'max_score' => $assessment['max_score'],
                    'score' => $submissionsByStudent[$student['id']]['score'],
                    'submitted' => true,
                    'submission_date' => $submissionsByStudent[$student['id']]['submission_date']
                ];
            } else {
                // Student has not submitted
                $scoreData[$student['id']]['assessments'][$assessment['id']] = [
                    'title' => $assessment['title'],
                    'max_score' => $assessment['max_score'],
                    'score' => null,
                    'submitted' => false,
                    'submission_date' => null
                ];
            }
        }
    }
    
    // Format file name with date
    $date = date('Y-m-d_H-i-s');
    $fileName = "SAP-MS_assessment_scores_" . $class['class_name'] . "_" . $date;
    
    // Calculate summary statistics
    $totalStudents = count($students);
    $totalAssessments = count($assessments);
    
    // Include TCPDF library
    require_once('tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('SAP-MS');
    $pdf->SetAuthor('SAP-MS System');
    $pdf->SetTitle('Assessment Scores Report');
    $pdf->SetSubject('Class Assessment Scores');
    $pdf->SetKeywords('SAP-MS, Scores, Assessment, Report');
    
    // Set default header data
    $pdf->SetHeaderData('', 0, 'SAP-MS - Assessment Scores Report', 'Generated on: ' . date('Y-m-d H:i:s'), array(0,64,255), array(0,64,128));
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
    $pdf->Cell(0, 10, 'Assessment Scores: ' . $class['class_name'], 0, 1, 'C');
    $pdf->Ln(2);
    
    // Class Information
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 6, 'Class: ' . $class['class_name'], 0, 1, 'L');
    $pdf->Cell(0, 6, 'Students: ' . $totalStudents, 0, 1, 'L');
    $pdf->Cell(0, 6, 'Assessments: ' . $totalAssessments, 0, 1, 'L');
    $pdf->Cell(0, 6, 'Date Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'L');
    $pdf->Ln(5);
    
    // Scores Table
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Assessment Scores', 0, 1, 'L');
    
    // Table header
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(52, 152, 219); // Blue header
    $pdf->SetTextColor(255);
    
    // Calculate column widths for the table
    $studentColWidth = 60; // Width for student name column
    $idColWidth = 25; // Width for student ID column
    
    // Determine remaining width for assessment columns
    $totalWidth = $pdf->getPageWidth() - 30; // Page width minus margins
    $assessmentColWidth = ($totalWidth - $studentColWidth - $idColWidth) / count($assessments);
    
    // Print header row
    $pdf->Cell($idColWidth, 10, 'Student ID', 1, 0, 'C', 1);
    $pdf->Cell($studentColWidth, 10, 'Student Name', 1, 0, 'C', 1);
    
    foreach ($assessments as $assessment) {
        // Format assessment title to fit in the column
        $title = strlen($assessment['title']) > 15 
            ? substr($assessment['title'], 0, 12) . '...' 
            : $assessment['title'];
        $pdf->Cell($assessmentColWidth, 10, $title, 1, 0, 'C', 1);
    }
    $pdf->Ln();
    
    // Table rows
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0);
    $pdf->SetFillColor(245, 247, 250); // Light blue for alternating rows
    
    $fillRow = false;
    
    // Print student scores
    foreach ($scoreData as $studentId => $data) {
        $pdf->Cell($idColWidth, 8, $data['student_code'], 1, 0, 'C', $fillRow);
        $pdf->Cell($studentColWidth, 8, $data['full_name'], 1, 0, 'L', $fillRow);
        
        foreach ($assessments as $assessment) {
            if (isset($data['assessments'][$assessment['id']])) {
                $scoreInfo = $data['assessments'][$assessment['id']];
                
                if ($scoreInfo['submitted']) {
                    // Format score as percentage
                    $scorePercentage = ($scoreInfo['score'] / $scoreInfo['max_score']) * 100;
                    $scoreDisplay = $scoreInfo['score'] . '/' . $scoreInfo['max_score'] . ' (' . round($scorePercentage) . '%)';
                    $pdf->Cell($assessmentColWidth, 8, $scoreDisplay, 1, 0, 'C', $fillRow);
                } else {
                    // Not submitted
                    $pdf->SetTextColor(231, 76, 60); // Red for missing
                    $pdf->Cell($assessmentColWidth, 8, 'Not Submitted', 1, 0, 'C', $fillRow);
                    $pdf->SetTextColor(0); // Reset text color
                }
            } else {
                // Assessment data not found
                $pdf->Cell($assessmentColWidth, 8, 'N/A', 1, 0, 'C', $fillRow);
            }
        }
        
        $pdf->Ln();
        $fillRow = !$fillRow; // Alternate row colors
    }
    
    $pdf->Ln(10);
    
    // Assessment Details Section
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Assessment Details', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    
    foreach ($assessments as $assessment) {
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, $assessment['title'], 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 9);
        
        // Format due date
        $dueDate = $assessment['due_date'] 
            ? date('Y-m-d H:i', strtotime($assessment['due_date'])) 
            : 'No due date';
            
        $pdf->Cell(0, 6, 'Due Date: ' . $dueDate, 0, 1, 'L');
        $pdf->Cell(0, 6, 'Maximum Score: ' . $assessment['max_score'], 0, 1, 'L');
        
        // Calculate submission statistics
        $submittedCount = 0;
        $totalScore = 0;
        
        foreach ($scoreData as $studentId => $data) {
            if (isset($data['assessments'][$assessment['id']]) && $data['assessments'][$assessment['id']]['submitted']) {
                $submittedCount++;
                $totalScore += $data['assessments'][$assessment['id']]['score'];
            }
        }
        
        $submissionRate = $totalStudents > 0 ? ($submittedCount / $totalStudents) * 100 : 0;
        $averageScore = $submittedCount > 0 ? $totalScore / $submittedCount : 0;
        
        $pdf->Cell(0, 6, 'Submission Rate: ' . round($submissionRate) . '% (' . $submittedCount . ' of ' . $totalStudents . ' students)', 0, 1, 'L');
        $pdf->Cell(0, 6, 'Average Score: ' . round($averageScore, 1) . ' / ' . $assessment['max_score'] . ' (' . round(($averageScore / $assessment['max_score']) * 100) . '%)', 0, 1, 'L');
        
        // Add a description if available
        if (!empty($assessment['description'])) {
            $pdf->Ln(2);
            $pdf->SetFont('helvetica', 'I', 9);
            $pdf->MultiCell(0, 6, 'Description: ' . $assessment['description'], 0, 'L');
        }
        
        $pdf->Ln(5);
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