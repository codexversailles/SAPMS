-- Create teacher-student private messages table
CREATE TABLE IF NOT EXISTS teacher_student_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    class_id INT NOT NULL, -- to link with the class context
    sender_type ENUM('student', 'teacher') NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE, -- to track unread messages
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_teacher_student (teacher_id, student_id),
    INDEX idx_class_id (class_id),
    INDEX idx_sender (sender_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 