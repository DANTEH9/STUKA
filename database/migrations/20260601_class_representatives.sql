CREATE TABLE IF NOT EXISTS class_representatives (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NULL,
    course_id INT UNSIGNED NOT NULL,
    academic_year_id INT UNSIGNED NOT NULL,
    semester_id INT UNSIGNED NOT NULL,
    study_year VARCHAR(60) NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cr_scope (course_id, academic_year_id, semester_id, study_year, status),
    INDEX idx_cr_student (student_id),
    CONSTRAINT fk_cr_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT fk_cr_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_cr_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    CONSTRAINT fk_cr_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    CONSTRAINT fk_cr_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cr_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO class_representatives (department_id, course_id, academic_year_id, semester_id, study_year, student_id, assigned_by, status, assigned_at)
SELECT 1, 1, 1, 2, '2nd Year', 5, 2, 'active', '2026-05-18 09:20:00'
WHERE EXISTS (SELECT 1 FROM users WHERE id = 5)
  AND EXISTS (SELECT 1 FROM course_registrations WHERE user_id = 5 AND course_id = 1 AND academic_year_id = 1 AND semester_id = 2 AND status = 'approved')
  AND NOT EXISTS (
      SELECT 1 FROM class_representatives
      WHERE course_id = 1
        AND academic_year_id = 1
        AND semester_id = 2
        AND study_year = '2nd Year'
        AND status = 'active'
  );
