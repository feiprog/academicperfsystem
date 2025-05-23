<?php
// Grade Categories
define('GRADE_CATEGORY_WRITTEN', 'written');
define('GRADE_CATEGORY_PERFORMANCE', 'performance');
define('GRADE_CATEGORY_EXAMS', 'exams');

// Grade Types
define('GRADE_TYPES', [
    GRADE_CATEGORY_WRITTEN => [
        'assignment' => ['weight' => 0.10, 'max_attempts' => null],
        'activity' => ['weight' => 0.10, 'max_attempts' => null],
        'quiz' => ['weight' => 0.10, 'max_attempts' => 2]
    ],
    GRADE_CATEGORY_PERFORMANCE => [
        'attendance' => ['weight' => 0.20, 'max_attempts' => null]
    ],
    GRADE_CATEGORY_EXAMS => [
        'prelim' => ['weight' => 0.10, 'max_attempts' => 1],
        'midterm' => ['weight' => 0.15, 'max_attempts' => 1],
        'semi_final' => ['weight' => 0.10, 'max_attempts' => 1],
        'final' => ['weight' => 0.15, 'max_attempts' => 1]
    ]
]);

// Category Weights
define('CATEGORY_WEIGHTS', [
    GRADE_CATEGORY_WRITTEN => 0.30,
    GRADE_CATEGORY_PERFORMANCE => 0.20,
    GRADE_CATEGORY_EXAMS => 0.50
]);

// Grade Status Thresholds
define('GRADE_STATUS_THRESHOLDS', [
    'Excellent' => 90,
    'Good' => 80,
    'Fair' => 70,
    'Needs Improvement' => 0
]);

// Decimal precision for grade calculations
define('GRADE_DECIMAL_PRECISION', 1);

// Academic terms
define('ACADEMIC_TERMS', [
    'First Semester',
    'Second Semester',
    'Summer'
]);

/**
 * Helper function to calculate weighted grade
 */
function calculateWeightedGrade($grades) {
    $total = 0;
    $weights = 0;
    
    foreach (GRADE_TYPES as $category => $types) {
        foreach ($types as $type => $config) {
            if (isset($grades[$type])) {
                $total += $grades[$type] * $config['weight'];
                $weights += $config['weight'];
            }
        }
    }
    
    return $weights > 0 ? round($total / $weights, GRADE_DECIMAL_PRECISION) : 0;
}

/**
 * Helper function to get grade status
 */
function getGradeStatus($grade) {
    foreach (GRADE_STATUS_THRESHOLDS as $status => $threshold) {
        if ($grade >= $threshold) {
            return $status;
        }
    }
    return 'No Grade';
}

/**
 * Helper function to validate grade value
 */
function validateGrade($grade) {
    return is_numeric($grade) && $grade >= 0 && $grade <= 100;
}

/**
 * Helper function to check grade attempt limits
 */
function checkGradeAttempts($category, $type, $student_id, $subject_id) {
    global $conn;
    
    $grade_type = GRADE_TYPES[$category][$type] ?? null;
    if (!$grade_type || !$grade_type['max_attempts']) {
        return true;
    }
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as attempts 
        FROM grades 
        WHERE student_id = ? 
        AND subject_id = ? 
        AND category = ? 
        AND grade_type = ?
    ");
    $stmt->bind_param("iiss", $student_id, $subject_id, $category, $type);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['attempts'] < $grade_type['max_attempts'];
} 