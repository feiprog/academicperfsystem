-- Drop existing grades table
DROP TABLE IF EXISTS grades;

-- Create updated grades table
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    category ENUM('written', 'performance', 'exams') NOT NULL,
    grade_type VARCHAR(20) NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    attempt_number INT DEFAULT 1,
    academic_year VARCHAR(9) NOT NULL,
    term VARCHAR(20) NOT NULL,
    remarks TEXT,
    graded_by INT NOT NULL,
    graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES teachers(id) ON DELETE CASCADE,
    -- Allow multiple attempts but ensure uniqueness per attempt
    UNIQUE KEY unique_grade_attempt (student_id, subject_id, category, grade_type, attempt_number, academic_year, term),
    INDEX idx_category (category),
    INDEX idx_grade_type (grade_type),
    INDEX idx_academic_year (academic_year),
    INDEX idx_term (term),
    INDEX idx_graded_at (graded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create grade_history table for audit trail
CREATE TABLE grade_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_id INT NOT NULL,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    category ENUM('written', 'performance', 'exams') NOT NULL,
    grade_type VARCHAR(20) NOT NULL,
    old_score DECIMAL(5,2),
    new_score DECIMAL(5,2) NOT NULL,
    modified_by INT NOT NULL,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason TEXT,
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (modified_by) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_grade_id (grade_id),
    INDEX idx_modified_at (modified_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 