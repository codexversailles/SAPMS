<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

try {
    // Get student ID from session for diagnostic purposes
    $student_id = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : 'Not logged in';
    
    // Check if notifications table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $tableExists = $tableCheck->rowCount() > 0;
    
    // Get notifications structure if table exists
    if ($tableExists) {
        $columnsQuery = $pdo->query("DESCRIBE notifications");
        $columns = $columnsQuery->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $columns = [];
    }
    
    // Check if any notifications exist in the database
    $totalNotifications = 0;
    $studentNotifications = 0;
    if ($tableExists) {
        $countQuery = $pdo->query("SELECT COUNT(*) FROM notifications");
        $totalNotifications = $countQuery->fetchColumn();
        
        if (isset($_SESSION['student_id'])) {
            $studentCountQuery = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE student_id = ?");
            $studentCountQuery->execute([$_SESSION['student_id']]);
            $studentNotifications = $studentCountQuery->fetchColumn();
            
            // Get sample notifications for this student
            $sampleQuery = $pdo->prepare("
                SELECT * FROM notifications 
                WHERE student_id = ? 
                ORDER BY created_at DESC LIMIT 5
            ");
            $sampleQuery->execute([$_SESSION['student_id']]);
            $notifications = $sampleQuery->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $notifications = [];
        }
    } else {
        $notifications = [];
    }
    
    // Check database connection details
    $connectionInfo = [
        'pdo_attributes' => [
            'ERRMODE' => $pdo->getAttribute(PDO::ATTR_ERRMODE),
            'CASE' => $pdo->getAttribute(PDO::ATTR_CASE),
            'DEFAULT_FETCH_MODE' => $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE),
        ],
        'driver_name' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
        'connection_status' => $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS),
    ];
    
    echo json_encode([
        'success' => true,
        'diagnostics' => [
            'student_id' => $student_id,
            'notifications_table' => [
                'exists' => $tableExists,
                'columns' => $columns,
                'total_notifications' => $totalNotifications,
                'student_notifications' => $studentNotifications
            ],
            'connection_info' => $connectionInfo,
            'sample_notifications' => $notifications,
            'session_data' => $_SESSION
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?> 