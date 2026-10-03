-- Table structure for table `class_view_logs`
CREATE TABLE `class_view_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `view_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `view_duration` int(11) DEFAULT NULL COMMENT 'Duration in seconds',
  PRIMARY KEY (`id`),
  KEY `student_class_index` (`student_id`, `class_id`),
  KEY `view_datetime_index` (`view_datetime`),
  CONSTRAINT `class_view_logs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_view_logs_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 