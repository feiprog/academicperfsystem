-- Create admin table
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    admin_id VARCHAR(20) UNIQUE,
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO users (email, password, full_name, role) 
VALUES ('admin@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Get the inserted user id
SET @admin_user_id = LAST_INSERT_ID();

-- Insert admin record
INSERT INTO admins (user_id, admin_id, department) 
VALUES (@admin_user_id, 'ADMIN001', 'IT Department'); 