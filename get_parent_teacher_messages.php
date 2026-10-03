<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in (either as parent or teacher)
if (!isset($_SESSION['parent_id']) && !isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Determine user type and ID
$isParent = isset($_SESSION['parent_id']);
$userId = $isParent ? $_SESSION['parent_id'] : $_SESSION['teacher_id'];
$userType = $isParent ? 'parent' : 'teacher';

// Get database connection
require_once 'db_connect.php';

// Get request parameters
$teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : null;
$parentId = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : null;
$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;

// Validate required parameters
if ($isParent && !$teacherId) {
    echo json_encode(['success' => false, 'message' => 'Missing teacher_id parameter']);
    exit;
}

if (!$isParent && !$parentId) {
    echo json_encode(['success' => false, 'message' => 'Missing parent_id parameter']);
    exit;
}

if (!$classId) {
    echo json_encode(['success' => false, 'message' => 'Missing class_id parameter']);
    exit;
}

// If parent, set parentId to the logged-in parent's ID
if ($isParent) {
    $parentId = $userId;
}

// If teacher, set teacherId to the logged-in teacher's ID
if (!$isParent) {
    $teacherId = $userId;
}

// Verify that the class exists
$stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ?");
$stmt->execute([$classId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
    exit;
}

// Verify that the teacher teaches this class
$stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
$stmt->execute([$classId, $teacherId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Teacher does not teach this class']);
    exit;
}

// If user is a parent, verify they have a child in this class
if ($isParent) {
    $stmt = $pdo->prepare("
        SELECT pc.id 
        FROM parent_children pc
        JOIN students s ON pc.student_id = s.student_id
        JOIN class_students cs ON cs.student_id = s.id
        WHERE pc.parent_id = ? AND cs.class_id = ?
    ");
    $stmt->execute([$parentId, $classId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You do not have a child in this class']);
        exit;
    }
}

// Fetch the messages between parent and teacher for this class
try {
    $stmt = $pdo->prepare("
        SELECT 
            ptm.*,
            p.parent_full_name,
            t.full_name as teacher_name,
            c.class_name
        FROM parent_teacher_messages ptm
        JOIN parents p ON ptm.parent_id = p.id
        JOIN teachers t ON ptm.teacher_id = t.id
        JOIN classes c ON ptm.class_id = c.id
        WHERE ptm.parent_id = ? AND ptm.teacher_id = ? AND ptm.class_id = ?
        ORDER BY ptm.created_at ASC
    ");
    
    $stmt->execute([$parentId, $teacherId, $classId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages as read if viewed by the recipient
    if (count($messages) > 0) {
        $senderType = $isParent ? 'teacher' : 'parent';
        $stmt = $pdo->prepare("
            UPDATE parent_teacher_messages
            SET is_read = 1
            WHERE parent_id = ? AND teacher_id = ? AND class_id = ? AND sender_type = ? AND is_read = 0
        ");
        $stmt->execute([$parentId, $teacherId, $classId, $senderType]);
    }
    
    // Format the message data for display
    $formattedMessages = [];
    foreach ($messages as $message) {
        $timestamp = strtotime($message['created_at']);
        $formattedDate = date('M j, Y', $timestamp);
        $formattedTime = date('g:i A', $timestamp);
        
        $formattedMessages[] = [
            'id' => $message['id'],
            'parent_id' => $message['parent_id'],
            'teacher_id' => $message['teacher_id'],
            'class_id' => $message['class_id'],
            'sender_type' => $message['sender_type'],
            'message' => $message['message'],
            'is_read' => (bool)$message['is_read'],
            'created_at' => $message['created_at'],
            'formatted_date' => $formattedDate,
            'formatted_time' => $formattedTime,
            'parent_name' => $message['parent_full_name'],
            'teacher_name' => $message['teacher_name'],
            'class_name' => $message['class_name']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $formattedMessages,
        'parent_name' => $formattedMessages[0]['parent_name'] ?? '',
        'teacher_name' => $formattedMessages[0]['teacher_name'] ?? '',
        'class_name' => $formattedMessages[0]['class_name'] ?? ''
    ]);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error', 'debug' => $e->getMessage()]);
} 