-- Adds a Research Method course for CS students.
-- Run in MySQL Workbench on database: grad_system
--
-- If your core_courses schema has additional NOT NULL columns, add them accordingly.

INSERT INTO core_courses (course_code, course_name, credits)
SELECT 'CS690', 'Research Methods in Computer Science', 3
WHERE NOT EXISTS (
  SELECT 1 FROM core_courses WHERE course_code = 'CS690'
);

