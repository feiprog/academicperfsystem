-- Insert teachers
INSERT INTO users (id, username, password, role, full_name, email, created_at) VALUES 
(1, 'mramos', '$2y$10$JzTCD/tsHHVJCB9AAQ6v3OBDWQBaTqph.siy5ePNBJog4Czg7t9cO', 'teacher', 'Marvin Ramos', 'marvin.ramos@school.edu', '2025-05-23 00:51:01'),
(2, 'sabina', '$2y$10$JzTCD/tsHHVJCB9AAQ6v3OBDWQBaTqph.siy5ePNBJog4Czg7t9cO', 'teacher', 'Shane Abina', 'shane.abina@school.edu', '2025-05-23 00:51:01'),
(3, 'jagudo', '$2y$10$JzTCD/tsHHVJCB9AAQ6v3OBDWQBaTqph.siy5ePNBJog4Czg7t9cO', 'teacher', 'Jovemer Agudo', 'jovemer.agudo@school.edu', '2025-05-23 00:51:01'),
(4, 'jsabalo', '$2y$10$JzTCD/tsHHVJCB9AAQ6v3OBDWQBaTqph.siy5ePNBJog4Czg7t9cO', 'teacher', 'Jonathan Sabalo', 'jonathan.sabalo@school.edu', '2025-05-23 00:51:01'),
(5, 'admin', '$2y$10$UC2vE74ojZfSQGQ3f5hKsuAttDecrapH9zCE1Arbd6EXkSQh343zO', 'admin', 'System Administrator', 'admin@school.edu', '2025-05-23 00:53:50'),
(6, 'test', '$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS', 'student', 'Test Student', 'test@example.com', '2025-05-23 00:55:29'),
(7, 'testuser', '$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS', 'student', 'Test User', 'testuser@example.com', '2025-05-23 01:00:58'),
(8, 'student1', '$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS', 'student', 'Student One', 'student1@example.com', '2025-05-23 01:02:29'),
(18, 'teststudent01', '$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS', 'student', 'Test Student', 'teststudent1@gmail.com', '2025-05-23 01:09:15');

-- Insert teacher records
INSERT INTO teachers (id, user_id, teacher_id, department, created_at) VALUES 
(1, 1, 'T2024-001', 'Information Technology', '2025-05-23 00:51:01'),
(2, 2, 'T2024-002', 'Information Technology', '2025-05-23 00:51:01'),
(3, 3, 'T2024-003', 'Information Technology', '2025-05-23 00:51:01'),
(4, 4, 'T2024-004', 'Information Technology', '2025-05-23 00:51:01'),
(11, 5, 'ADMIN-001', 'System Administration', '2025-05-23 01:14:30');

-- Insert student records
INSERT INTO students (id, user_id, student_id, first_name, last_name, year_level, degree_program, semester, academic_year, created_at) VALUES 
(2, 6, 'STU20240001', 'Test', 'Student', '1st Year', 'BSIT', 'First Semester', '2023-2024', '2025-05-23 00:59:40'),
(3, 7, 'STU20240002', 'Test', 'User', '1st Year', 'BSIT', 'First Semester', '2023-2024', '2025-05-23 01:01:07'),
(4, 8, 'STU20240003', 'Student', 'One', '1st Year', 'BSIT', 'First Semester', '2023-2024', '2025-05-23 01:02:40'),
(9, 18, 'STU20250018', 'Test', 'Student', '2nd Year', 'BSIT', 'Second Semester', '2024-2025', '2025-05-23 01:09:15'); 