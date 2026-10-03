-- Create a new table for parent-child relationships (many-to-many)
CREATE TABLE `parent_children` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `relationship` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `parent_student_unique` (`parent_id`,`student_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `parent_children_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `parent_children_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Migrate existing data from parents table to the new table
INSERT INTO parent_children (parent_id, student_id, relationship)
SELECT id, student_id, relationship FROM parents;

-- Now we need to update the parents table to remove the student-specific fields
-- since they will now be in the parent_children table
ALTER TABLE `parents` 
  DROP FOREIGN KEY `parents_ibfk_1`;

ALTER TABLE `parents`
  DROP COLUMN `student_id`,
  DROP COLUMN `student_full_name`,
  DROP COLUMN `relationship`;

-- Add a trigger to validate student exists before adding to parent_children
DELIMITER $$
CREATE TRIGGER `before_parent_child_insert` BEFORE INSERT ON `parent_children` FOR EACH ROW
BEGIN
    DECLARE student_exists INT;
    DECLARE student_name VARCHAR(100);
    
    -- Check if student exists with the given ID
    SELECT COUNT(*), full_name INTO student_exists, student_name
    FROM students
    WHERE student_id = NEW.student_id;
    
    -- If no matching student found, raise an error
    IF student_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid student ID. Please ensure the student is registered first.';
    END IF;
END$$
DELIMITER ;

-- Drop the old triggers that are no longer needed
DROP TRIGGER IF EXISTS before_parent_insert;
DROP TRIGGER IF EXISTS before_parent_update; 