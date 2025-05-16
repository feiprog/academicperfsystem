CREATE TABLE teacher_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL, -- links to the student's report request
    teacher_id INT NOT NULL, -- who responded
    report_text TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES student_reports(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id)
); 