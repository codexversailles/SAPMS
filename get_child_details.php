<?php
session_start();
header('Content-Type: application/json');

// Validate student_id parameter
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

$studentId = $_GET['student_id'];

// Check if this is a parent login or direct access (for viewing student dashboards)
$isParentLoggedIn = isset($_SESSION['parent_id']);
if (!$isParentLoggedIn) {
    // No parent login check for direct access - we'll display a view-only version
    // This is safe because we're only returning public information about the student
}

// Database connection
$host = 'localhost';
$dbname = 'studyhub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
    exit;
}

try {
    // If parent is logged in, verify that the student is linked to the parent
    if ($isParentLoggedIn) {
        $parentId = $_SESSION['parent_id'];
        $stmt = $pdo->prepare("
            SELECT id FROM parent_children 
            WHERE parent_id = ? AND student_id = ?
        ");
        $stmt->execute([$parentId, $studentId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You are not authorized to view this student information']);
            exit;
        }
    }
    
    // Get student details
    $stmt = $pdo->prepare("
        SELECT id, full_name, student_id, email 
        FROM students 
        WHERE student_id = ?
    ");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    
    // Store the internal student ID (numeric ID)
    $internalStudentId = $student['id'];
    
    // Get classes the student is enrolled in
    $stmt = $pdo->prepare("
        SELECT c.id as class_id, c.class_name, c.class_code, t.id as teacher_id, t.full_name as teacher_name, t.email as teacher_email
        FROM classes c
        JOIN class_students cs ON c.id = cs.class_id
        JOIN teachers t ON c.teacher_id = t.id
        JOIN students s ON cs.student_id = s.id
        WHERE s.id = ?
    ");
    $stmt->execute([$internalStudentId]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get assessment scores - FIXED to only show assessments from enrolled classes
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.description, a.max_score, a.due_date, 
               c.class_name, c.id as class_id, t.id as teacher_id, t.full_name as teacher_name,
               ss.score, ss.submission_date, ss.teacher_feedback
        FROM assessments a
        JOIN classes c ON a.class_id = c.id
        JOIN teachers t ON c.teacher_id = t.id
        JOIN class_students cs ON c.id = cs.class_id
        JOIN students s ON cs.student_id = s.id
        LEFT JOIN student_submissions ss ON a.id = ss.assessment_id AND ss.student_id = s.id
        WHERE s.id = ?
        ORDER BY a.due_date DESC
    ");
    $stmt->execute([$internalStudentId]);
    $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get lessons in the student's classes
    $stmt = $pdo->prepare("
        SELECT l.id, l.title, l.description, l.created_at, 
               c.class_name, c.id as class_id, t.id as teacher_id, t.full_name as teacher_name
        FROM lessons l
        JOIN classes c ON l.class_id = c.id
        JOIN teachers t ON c.teacher_id = t.id
        JOIN class_students cs ON c.id = cs.class_id
        JOIN students s ON cs.student_id = s.id
        WHERE s.id = ?
        ORDER BY l.created_at DESC
    ");
    $stmt->execute([$internalStudentId]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get announcements for the student's classes (both class and parent visibility)
    try {
        // First, get the class IDs this student is enrolled in
        $stmt = $pdo->prepare("
            SELECT c.id
            FROM classes c
            JOIN class_students cs ON c.id = cs.class_id
            WHERE cs.student_id = ?
        ");
        $stmt->execute([$internalStudentId]);
        $classIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($classIds)) {
            $placeholders = implode(',', array_fill(0, count($classIds), '?'));
            
            $announcementSql = "
                SELECT a.id, a.title, a.content, a.visibility, a.priority, a.created_at, 
                    c.class_name, c.id as class_id, t.full_name as teacher_name
                FROM announcements a
                JOIN classes c ON a.class_id = c.id
                JOIN teachers t ON a.teacher_id = t.id
                WHERE a.class_id IN ($placeholders) AND (a.visibility = 'parents' OR a.visibility = 'class')
                ORDER BY a.created_at DESC
            ";
            $stmt = $pdo->prepare($announcementSql);
            $stmt->execute($classIds);
            $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $announcements = [];
        }
    } catch (Exception $e) {
        // Log the error but continue with empty announcements
        error_log('Error fetching announcements: ' . $e->getMessage());
        $announcements = [];
    }
    
    // Return all the collected data
    echo json_encode([
        'success' => true,
        'student' => $student,
        'classes' => $classes,
        'assessments' => $assessments,
        'lessons' => $lessons,
        'announcements' => $announcements,
        'debug_info' => [
            'announcement_count' => count($announcements),
            'internal_student_id' => $internalStudentId,
            'class_ids' => $classIds ?? []
        ]
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching data: ' . $e->getMessage()]);
}
?> 