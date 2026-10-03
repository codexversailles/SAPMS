<?php
// This script creates the parent_teacher_messages table in the database

// Get database connection
require_once 'db_connect.php';

// SQL to create the parent_teacher_messages table
$sql = "
CREATE TABLE IF NOT EXISTS `parent_teacher_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `sender_type` enum('parent','teacher') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_parent_teacher` (`parent_id`,`teacher_id`),
  KEY `idx_class_id` (`class_id`),
  KEY `idx_sender` (`sender_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `parent_teacher_messages_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `parent_teacher_messages_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `parent_teacher_messages_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    // Run the SQL
    $pdo->exec($sql);
    echo "Parent Teacher Messages table created successfully. <br>";
} catch (PDOException $e) {
    echo "Error creating Parent Teacher Messages table: " . $e->getMessage() . "<br>";
}

// Add some sample data
try {
    // Get a parent ID
    $stmt = $pdo->query("SELECT id FROM parents LIMIT 1");
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);
    $parentId = $parent['id'];
    
    // Get a teacher ID
    $stmt = $pdo->query("SELECT id FROM teachers LIMIT 1");
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    $teacherId = $teacher['id'];
    
    // Get a class ID
    $stmt = $pdo->query("SELECT id FROM classes LIMIT 1");
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    $classId = $class['id'];
    
    // Check if we have sample data
    if ($parentId && $teacherId && $classId) {
        // Insert sample messages
        $sampleMessages = [
            // Parent message
            [
                'parent_id' => $parentId,
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'sender_type' => 'parent',
                'message' => 'Hello teacher, I wanted to ask about my child\'s progress in your class.'
            ],
            // Teacher response
            [
                'parent_id' => $parentId,
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'sender_type' => 'teacher',
                'message' => 'Hello! Your child is doing well in class. They\'ve been very active in discussions.'
            ],
            // Parent follow-up
            [
                'parent_id' => $parentId,
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'sender_type' => 'parent',
                'message' => 'That\'s great to hear! Is there anything specific we should work on at home?'
            ],
            // Teacher response
            [
                'parent_id' => $parentId,
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'sender_type' => 'teacher',
                'message' => 'I would recommend focusing on the upcoming project. It\'s a significant part of their grade.'
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO parent_teacher_messages 
            (parent_id, teacher_id, class_id, sender_type, message) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($sampleMessages as $message) {
            $stmt->execute([
                $message['parent_id'],
                $message['teacher_id'],
                $message['class_id'],
                $message['sender_type'],
                $message['message']
            ]);
        }
        
        echo "Sample messages added successfully.";
    } else {
        echo "Could not add sample data - missing parent, teacher, or class records.";
    }
    
} catch (PDOException $e) {
    echo "Error adding sample data: " . $e->getMessage();
} 