-- Check if table exists and create if not
CREATE TABLE IF NOT EXISTS `class_chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('teacher','student') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `sender_id` (`sender_id`,`sender_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert a sample message for testing
INSERT INTO `class_chat_messages` (`class_id`, `sender_id`, `sender_type`, `message`)
VALUES
(3, 1, 'teacher', 'Welcome to the class chat! This is a test message.'),
(3, 5, 'student', 'Thank you for creating this chat!'); 