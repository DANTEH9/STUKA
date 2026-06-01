USE stuca;

ALTER TABLE assignments
    ADD COLUMN IF NOT EXISTS date_given DATE NULL AFTER instructions;

UPDATE assignments SET date_given = DATE(created_at) WHERE date_given IS NULL;

ALTER TABLE assignment_submissions
    ADD COLUMN IF NOT EXISTS student_comment TEXT NULL AFTER original_name,
    ADD COLUMN IF NOT EXISTS is_late TINYINT(1) NOT NULL DEFAULT 0 AFTER submitted_at,
    ADD COLUMN IF NOT EXISTS late_duration_minutes INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_late,
    ADD COLUMN IF NOT EXISTS reviewed_by INT UNSIGNED NULL AFTER feedback,
    ADD COLUMN IF NOT EXISTS reviewed_at DATETIME NULL AFTER reviewed_by,
    ADD COLUMN IF NOT EXISTS review_remarks TEXT NULL AFTER reviewed_at;

CREATE TABLE IF NOT EXISTS ca_results (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    module_id INT UNSIGNED NOT NULL,
    academic_year_id INT UNSIGNED NULL,
    semester_id INT UNSIGNED NULL,
    class_group VARCHAR(80) NULL,
    total_ca DECIMAL(5,2) NOT NULL DEFAULT 0,
    max_ca DECIMAL(5,2) NOT NULL DEFAULT 60,
    lecturer_remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ca_result (student_id, module_id, academic_year_id, semester_id, class_group),
    INDEX idx_ca_results_student (student_id),
    INDEX idx_ca_results_module (module_id),
    CONSTRAINT fk_ca_results_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ca_results_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_ca_results_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    CONSTRAINT fk_ca_results_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE SET NULL,
    CONSTRAINT fk_ca_results_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assessment_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ca_result_id INT UNSIGNED NOT NULL,
    item_name VARCHAR(120) NOT NULL,
    score DECIMAL(5,2) NOT NULL DEFAULT 0,
    max_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ca_item (ca_result_id, item_name),
    INDEX idx_assessment_items_result (ca_result_id),
    CONSTRAINT fk_assessment_items_result FOREIGN KEY (ca_result_id) REFERENCES ca_results(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    actor_id INT UNSIGNED NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id INT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_notifications_user (user_id, is_read, created_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

UPDATE assignments SET submission_type = 'Online / Email' WHERE submission_type IN ('Online upload', 'Email');
UPDATE assignments SET submission_type = 'Hard Copy' WHERE submission_type = 'Hard copy';

INSERT IGNORE INTO ca_results (id, student_id, course_id, module_id, academic_year_id, semester_id, class_group, total_ca, max_ca, lecturer_remarks) VALUES
(1, 5, 1, 1, 1, 1, 'OD23IT', 47, 60, 'Strong practical effort. Improve speed on test questions.'),
(2, 5, 1, 3, 1, 2, 'OD23IT', 44, 60, 'Good database design progress. Revise normalization edge cases.');

INSERT IGNORE INTO assessment_items (ca_result_id, item_name, score, max_score, sort_order) VALUES
(1, 'Test 1', 8, 10, 1),
(1, 'Test 2', 7, 10, 2),
(1, 'Quiz', 5, 5, 3),
(1, 'Assignment 1', 8, 10, 4),
(1, 'Assignment 2', 7, 10, 5),
(1, 'Group Assignment', 4, 5, 6),
(1, 'Presentation', 4, 5, 7),
(1, 'Practical', 4, 5, 8),
(2, 'Test 1', 7, 10, 1),
(2, 'Test 2', 7, 10, 2),
(2, 'Quiz', 4, 5, 3),
(2, 'Assignment 1', 8, 10, 4),
(2, 'Assignment 2', 6, 10, 5),
(2, 'Group Assignment', 4, 5, 6),
(2, 'Presentation', 4, 5, 7),
(2, 'Practical', 4, 5, 8);
