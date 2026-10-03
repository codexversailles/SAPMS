<?php
session_start();
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header to return JSON
header('Content-Type: application/json');

try {
    // Check if user is logged in as teacher
    if (!isset($_SESSION['teacher_id'])) {
        throw new Exception('Not authorized');
    }

    // Get assessment ID from query parameters
    $assessment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$assessment_id) {
        throw new Exception('Assessment ID is required');
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // First, get all file paths associated with this assessment
        $file_query = "SELECT file_path FROM assessment_files WHERE assessment_id = ?";
        $file_stmt = $pdo->prepare($file_query);
        $file_stmt->execute([$assessment_id]);
        $files = $file_stmt->fetchAll(PDO::FETCH_COLUMN);

        // Delete physical files
        foreach ($files as $file_path) {
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Delete records from database tables
        // First delete from assessment_files (due to foreign key constraint)
        $delete_files = "DELETE FROM assessment_files WHERE assessment_id = ?";
        $stmt = $pdo->prepare($delete_files);
        $stmt->execute([$assessment_id]);

        // Then delete from student_submissions (due to foreign key constraint)
        $delete_submissions = "DELETE FROM student_submissions WHERE assessment_id = ?";
        $stmt = $pdo->prepare($delete_submissions);
        $stmt->execute([$assessment_id]);

        // Finally delete the assessment
        $delete_assessment = "DELETE FROM assessments WHERE id = ?";
        $stmt = $pdo->prepare($delete_assessment);
        $stmt->execute([$assessment_id]);

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Assessment deleted successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?> 