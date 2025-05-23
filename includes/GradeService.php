<?php
require_once 'grade_constants.php';

class GradeService {
    private $conn;
    private $current_user;
    
    public function __construct($conn, $current_user) {
        $this->conn = $conn;
        $this->current_user = $current_user;
    }
    
    /**
     * Submit a new grade with proper validation and transaction handling
     */
    public function submitGrade($data) {
        try {
            // Validate input
            if (!$this->validateGradeInput($data)) {
                throw new Exception('Invalid grade input');
            }
            
            // Start transaction
            $this->conn->begin_transaction();
            
            // Check attempt limits
            if (!checkGradeAttempts($data['category'], $data['grade_type'], $data['student_id'], $data['subject_id'])) {
                throw new Exception('Maximum attempts reached for this grade type');
            }
            
            // Get current attempt number
            $attempt = $this->getNextAttemptNumber($data);
            
            // Insert grade
            $stmt = $this->conn->prepare("
                INSERT INTO grades (
                    student_id, subject_id, category, grade_type, score, 
                    attempt_number, academic_year, term, remarks, graded_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "iisssisssi",
                $data['student_id'],
                $data['subject_id'],
                $data['category'],
                $data['grade_type'],
                $data['score'],
                $attempt,
                $data['academic_year'],
                $data['term'],
                $data['remarks'],
                $this->current_user['id']
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to insert grade');
            }
            
            $grade_id = $stmt->insert_id;
            
            // Record in history
            $this->recordGradeHistory($grade_id, $data, null, $data['score'], 'Initial grade submission');
            
            // Commit transaction
            $this->conn->commit();
            
            return [
                'success' => true,
                'grade_id' => $grade_id,
                'message' => 'Grade submitted successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update an existing grade
     */
    public function updateGrade($grade_id, $data) {
        try {
            // Validate input
            if (!$this->validateGradeInput($data)) {
                throw new Exception('Invalid grade input');
            }
            
            // Start transaction
            $this->conn->begin_transaction();
            
            // Get current grade
            $stmt = $this->conn->prepare("SELECT * FROM grades WHERE id = ?");
            $stmt->bind_param("i", $grade_id);
            $stmt->execute();
            $current_grade = $stmt->get_result()->fetch_assoc();
            
            if (!$current_grade) {
                throw new Exception('Grade not found');
            }
            
            // Update grade
            $stmt = $this->conn->prepare("
                UPDATE grades 
                SET score = ?, remarks = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $stmt->bind_param("dsi", $data['score'], $data['remarks'], $grade_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update grade');
            }
            
            // Record in history
            $this->recordGradeHistory(
                $grade_id,
                $data,
                $current_grade['score'],
                $data['score'],
                $data['reason'] ?? 'Grade update'
            );
            
            // Commit transaction
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Grade updated successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate overall grade for a student in a subject
     */
    public function calculateOverallGrade($student_id, $subject_id, $term = null) {
        $sql = "
            SELECT 
                category,
                grade_type,
                score
            FROM grades 
            WHERE student_id = ? 
            AND subject_id = ?
        ";
        
        $params = [$student_id, $subject_id];
        $types = "ii";
        
        if ($term) {
            $sql .= " AND term = ?";
            $params[] = $term;
            $types .= "s";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $grades = [];
        while ($row = $result->fetch_assoc()) {
            $grades[$row['grade_type']] = $row['score'];
        }
        
        return calculateWeightedGrade($grades);
    }
    
    /**
     * Get grade history for a specific grade
     */
    public function getGradeHistory($grade_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                gh.*,
                CONCAT(u.first_name, ' ', u.last_name) as modified_by_name
            FROM grade_history gh
            JOIN teachers t ON gh.modified_by = t.id
            JOIN users u ON t.user_id = u.id
            WHERE gh.grade_id = ?
            ORDER BY gh.modified_at DESC
        ");
        
        $stmt->bind_param("i", $grade_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    private function validateGradeInput($data) {
        // Check required fields
        $required = ['student_id', 'subject_id', 'category', 'grade_type', 'score', 'academic_year', 'term'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        
        // Validate score
        if (!validateGrade($data['score'])) {
            return false;
        }
        
        // Validate category and grade type
        if (!isset(GRADE_TYPES[$data['category']][$data['grade_type']])) {
            return false;
        }
        
        return true;
    }
    
    private function getNextAttemptNumber($data) {
        $stmt = $this->conn->prepare("
            SELECT MAX(attempt_number) as last_attempt
            FROM grades
            WHERE student_id = ?
            AND subject_id = ?
            AND category = ?
            AND grade_type = ?
            AND academic_year = ?
            AND term = ?
        ");
        
        $stmt->bind_param(
            "iissss",
            $data['student_id'],
            $data['subject_id'],
            $data['category'],
            $data['grade_type'],
            $data['academic_year'],
            $data['term']
        );
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return ($result['last_attempt'] ?? 0) + 1;
    }
    
    private function recordGradeHistory($grade_id, $data, $old_score, $new_score, $reason) {
        $stmt = $this->conn->prepare("
            INSERT INTO grade_history (
                grade_id, student_id, subject_id, category, grade_type,
                old_score, new_score, modified_by, reason
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iiissdsis",
            $grade_id,
            $data['student_id'],
            $data['subject_id'],
            $data['category'],
            $data['grade_type'],
            $old_score,
            $new_score,
            $this->current_user['id'],
            $reason
        );
        
        return $stmt->execute();
    }
} 