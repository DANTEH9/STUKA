CREATE DATABASE IF NOT EXISTS stuca CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stuca;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS results;
DROP TABLE IF EXISTS timetables;
DROP TABLE IF EXISTS past_papers;
DROP TABLE IF EXISTS materials;
DROP TABLE IF EXISTS assignment_submissions;
DROP TABLE IF EXISTS assignments;
DROP TABLE IF EXISTS assessment_items;
DROP TABLE IF EXISTS ca_results;
DROP TABLE IF EXISTS lecturer_courses;
DROP TABLE IF EXISTS course_registrations;
DROP TABLE IF EXISTS modules;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS semesters;
DROP TABLE IF EXISTS academic_years;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS roles;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    code VARCHAR(30) NOT NULL UNIQUE,
    description TEXT NULL,
    head_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_departments_code (code)
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NULL,
    name VARCHAR(140) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    student_number VARCHAR(60) NULL UNIQUE,
    staff_number VARCHAR(60) NULL UNIQUE,
    phone VARCHAR(40) NULL,
    program VARCHAR(160) NULL,
    class_group VARCHAR(80) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role_id),
    INDEX idx_users_department (department_id),
    INDEX idx_users_status (status),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
    CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE departments
    ADD CONSTRAINT fk_departments_head FOREIGN KEY (head_user_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE academic_years (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30) NOT NULL UNIQUE,
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    is_current BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE semesters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year_id INT UNSIGNED NOT NULL,
    name VARCHAR(60) NOT NULL,
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    is_current BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_semester_year (academic_year_id, name),
    INDEX idx_semesters_current (is_current),
    CONSTRAINT fk_semesters_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NULL,
    code VARCHAR(40) NOT NULL UNIQUE,
    title VARCHAR(190) NOT NULL,
    description TEXT NULL,
    level VARCHAR(80) NULL,
    credits SMALLINT UNSIGNED NOT NULL DEFAULT 3,
    status ENUM('active', 'archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_courses_department (department_id),
    INDEX idx_courses_status (status),
    CONSTRAINT fk_courses_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE modules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    semester_id INT UNSIGNED NULL,
    code VARCHAR(40) NOT NULL UNIQUE,
    title VARCHAR(190) NOT NULL,
    description TEXT NULL,
    credits TINYINT UNSIGNED NOT NULL DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_modules_course (course_id),
    INDEX idx_modules_semester (semester_id),
    CONSTRAINT fk_modules_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_modules_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE course_registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    academic_year_id INT UNSIGNED NOT NULL,
    semester_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_registration (user_id, course_id, academic_year_id, semester_id),
    INDEX idx_registrations_status (status),
    INDEX idx_registrations_course (course_id),
    CONSTRAINT fk_registrations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_registrations_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_registrations_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    CONSTRAINT fk_registrations_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    CONSTRAINT fk_registrations_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE lecturer_courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    academic_year_id INT UNSIGNED NULL,
    semester_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_lecturer_course (lecturer_id, course_id, academic_year_id, semester_id),
    INDEX idx_lecturer_courses_course (course_id),
    CONSTRAINT fk_lecturer_courses_lecturer FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_lecturer_courses_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_lecturer_courses_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE SET NULL,
    CONSTRAINT fk_lecturer_courses_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    module_id INT UNSIGNED NULL,
    lecturer_id INT UNSIGNED NULL,
    semester_id INT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    instructions TEXT NULL,
    date_given DATE NULL,
    deadline DATE NOT NULL,
    submission_type VARCHAR(80) NOT NULL DEFAULT 'Online upload',
    file_path VARCHAR(255) NULL,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_assignments_course (course_id),
    INDEX idx_assignments_module (module_id),
    INDEX idx_assignments_deadline (deadline),
    CONSTRAINT fk_assignments_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL,
    CONSTRAINT fk_assignments_lecturer FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_assignments_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE assignment_submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(190) NOT NULL,
    student_comment TEXT NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_late TINYINT(1) NOT NULL DEFAULT 0,
    late_duration_minutes INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('submitted', 'resubmitted', 'graded') NOT NULL DEFAULT 'submitted',
    grade VARCHAR(20) NULL,
    feedback TEXT NULL,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_submission (assignment_id, student_id),
    INDEX idx_submissions_student (student_id),
    INDEX idx_submissions_reviewed (reviewed_at),
    CONSTRAINT fk_submissions_assignment FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    CONSTRAINT fk_submissions_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_submissions_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE ca_results (
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

CREATE TABLE assessment_items (
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

CREATE TABLE materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    module_id INT UNSIGNED NULL,
    uploaded_by INT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    file_name VARCHAR(190) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size VARCHAR(40) NOT NULL,
    file_type VARCHAR(20) NOT NULL,
    visibility ENUM('course', 'public') NOT NULL DEFAULT 'course',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_materials_course (course_id),
    INDEX idx_materials_module (module_id),
    INDEX idx_materials_type (file_type),
    CONSTRAINT fk_materials_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_materials_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL,
    CONSTRAINT fk_materials_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE past_papers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    module_id INT UNSIGNED NULL,
    uploaded_by INT UNSIGNED NULL,
    academic_year_id INT UNSIGNED NOT NULL,
    semester_id INT UNSIGNED NULL,
    study_year VARCHAR(60) NOT NULL,
    exam_type VARCHAR(80) NOT NULL,
    title VARCHAR(190) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(190) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_papers_course (course_id),
    INDEX idx_papers_module (module_id),
    INDEX idx_papers_year (academic_year_id),
    CONSTRAINT fk_papers_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_papers_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL,
    CONSTRAINT fk_papers_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_papers_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE,
    CONSTRAINT fk_papers_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE timetables (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    module_id INT UNSIGNED NULL,
    lecturer_id INT UNSIGNED NULL,
    semester_id INT UNSIGNED NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(80) NOT NULL,
    class_group VARCHAR(80) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_timetables_course (course_id),
    INDEX idx_timetables_day (day_of_week),
    CONSTRAINT fk_timetables_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_timetables_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL,
    CONSTRAINT fk_timetables_lecturer FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_timetables_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE results (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    module_id INT UNSIGNED NULL,
    semester_id INT UNSIGNED NULL,
    academic_year_id INT UNSIGNED NULL,
    uploaded_by INT UNSIGNED NULL,
    ca_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    exam_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    total_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    grade VARCHAR(12) NOT NULL,
    status ENUM('pass', 'repeat', 'incomplete') NOT NULL DEFAULT 'pass',
    released_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_results_student (student_id),
    INDEX idx_results_course (course_id),
    INDEX idx_results_status (status),
    CONSTRAINT fk_results_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_results_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_results_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL,
    CONSTRAINT fk_results_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL,
    CONSTRAINT fk_results_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE SET NULL,
    CONSTRAINT fk_results_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE announcements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NULL,
    module_id INT UNSIGNED NULL,
    department_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    audience ENUM('global', 'department', 'course') NOT NULL DEFAULT 'global',
    title VARCHAR(190) NOT NULL,
    body TEXT NOT NULL,
    publish_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_announcements_audience (audience),
    INDEX idx_announcements_publish_at (publish_at),
    CONSTRAINT fk_announcements_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_announcements_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL,
    CONSTRAINT fk_announcements_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    CONSTRAINT fk_announcements_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE notifications (
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

CREATE TABLE activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(190) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id INT UNSIGNED NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activity_user (user_id),
    INDEX idx_activity_entity (entity_type, entity_id),
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO roles (id, slug, name, description) VALUES
(1, 'super_admin', 'Super Admin', 'Full system ownership and database record management.'),
(2, 'admin', 'Admin', 'Academic operations and course administration.'),
(3, 'department_head', 'Department Head', 'Department overview, reports, and lecturer activity.'),
(4, 'lecturer', 'Lecturer', 'Teaching activities, uploads, assignments, and results.'),
(5, 'student', 'Student', 'Learning resources, registration, results, and submissions.');

INSERT INTO departments (id, name, code, description) VALUES
(1, 'School of Computing', 'SOC', 'Computing, software, networks, and data programmes.'),
(2, 'Business Studies', 'BUS', 'Business, accounting, procurement, and entrepreneurship.');

INSERT INTO users (id, role_id, department_id, name, email, password_hash, status, student_number, staff_number, phone, program, class_group) VALUES
(1, 1, NULL, 'Amina Kessy', 'super@stuca.local', '$2a$12$CdZLlhTSQUFGE3SE24knme78aQDLY/H.sRtSK4JQSGLHaJietvzwi', 'active', NULL, 'STF-001', '+255 712 440 118', 'System Administration', 'HQ'),
(2, 2, NULL, 'Brian Mwakalinga', 'admin@stuca.local', '$2a$12$r3NzKlZcBpSkcUZ8QfQFH.nNSPdawk9TWsPlb3lQ8DNaT60xI7k8m', 'active', NULL, 'STF-018', '+255 713 918 204', 'Academic Operations', 'Registry'),
(3, 3, 1, 'Dr. Rehema Said', 'head@stuca.local', '$2a$12$l9y5FY7F7U/jtgZ0VWvn4OxBHN797QhCkRjfwrnsw9H5aOxNcz9Uq', 'active', NULL, 'STF-023', '+255 755 602 331', 'School of Computing', 'SOC'),
(4, 4, 1, 'Mr. Joseph Nkya', 'lecturer@stuca.local', '$2a$12$JSAfJPfYRu5Zwtyu00KUzehiQ49U0kV0zZsfxoWHkTNma2z/mC1/6', 'active', NULL, 'STF-041', '+255 744 290 812', 'Software Engineering', 'Lecturer'),
(5, 5, 1, 'Neema Baraka', 'student@stuca.local', '$2a$12$vrmZAzpBfB1q.UU51iQufORmXpwbg2YNbLMs/3ZFr7Yh9MNzOEz2W', 'active', 'OD23IT-014', NULL, '+255 768 501 726', 'Diploma in Information Technology', 'OD23IT');

UPDATE departments SET head_user_id = 3 WHERE id = 1;

INSERT INTO academic_years (id, name, starts_on, ends_on, is_current) VALUES
(1, '2025/26', '2025-08-01', '2026-07-31', TRUE),
(2, '2024/25', '2024-08-01', '2025-07-31', FALSE);

INSERT INTO semesters (id, academic_year_id, name, starts_on, ends_on, is_current) VALUES
(1, 1, 'Semester 1', '2025-08-01', '2025-12-20', FALSE),
(2, 1, 'Semester 2', '2026-01-12', '2026-06-20', TRUE),
(3, 2, 'Semester 2', '2025-01-13', '2025-06-22', FALSE);

INSERT INTO courses (id, department_id, code, title, description, level, credits, status) VALUES
(1, 1, 'DIT201', 'Diploma in Information Technology', 'Practical software, database, networking, and support skills.', 'Diploma', 120, 'active'),
(2, 1, 'BCS301', 'Bachelor of Computer Science', 'Computer science foundations, systems, and software design.', 'Degree', 360, 'active'),
(3, 2, 'DBA110', 'Diploma in Business Administration', 'Operations, communication, accounting, and office systems.', 'Diploma', 120, 'active');

INSERT INTO modules (id, course_id, semester_id, code, title, description, credits) VALUES
(1, 1, 1, 'ITP101', 'Programming Fundamentals', 'Problem solving, variables, control flow, and structured programming.', 4),
(2, 1, 2, 'WEB204', 'Web Application Development', 'HTML, CSS, JavaScript, PHP, and database-backed web applications.', 4),
(3, 1, 2, 'DBS202', 'Database Systems', 'Relational design, normalization, SQL, and transactions.', 4),
(4, 1, 1, 'NET205', 'Computer Networks', 'Network models, switching, routing, and campus network services.', 3);

INSERT INTO lecturer_courses (lecturer_id, course_id, academic_year_id, semester_id) VALUES
(4, 1, 1, 2),
(3, 1, 1, 2);

INSERT INTO course_registrations (id, user_id, course_id, academic_year_id, semester_id, status, requested_at, reviewed_by, reviewed_at, notes) VALUES
(1, 5, 1, 1, 2, 'approved', '2026-02-01 08:12:00', 2, '2026-02-01 11:05:00', 'Continuing student'),
(2, 5, 2, 1, 2, 'pending', '2026-05-12 13:41:00', NULL, NULL, 'Requested transfer evaluation');

INSERT INTO assignments (id, course_id, module_id, lecturer_id, semester_id, title, instructions, date_given, deadline, submission_type, status) VALUES
(1, 1, 3, 3, 2, 'Normalized schema design', 'Design an ERD and submit SQL create statements.', '2026-05-28', '2026-06-14', 'Online / Email', 'open'),
(2, 1, 2, 4, 2, 'PHP dashboard project', 'Build a secure CRUD dashboard with role checks.', '2026-05-30', '2026-06-21', 'Online / Email', 'open'),
(3, 1, 1, 4, 1, 'Algorithm practice set', 'Solve ten array and loop exercises.', '2026-05-20', '2026-06-07', 'Hard Copy', 'closed');

INSERT INTO materials (course_id, module_id, uploaded_by, title, file_name, file_path, file_size, file_type, visibility) VALUES
(1, 2, 4, 'PHP forms and PDO', 'php-forms-pdo.pdf', 'uploads/materials/php-forms-pdo.pdf', '1.8 MB', 'PDF', 'course'),
(1, 3, 3, 'Normalization tutorial', 'normalization-tutorial.pdf', 'uploads/materials/normalization-tutorial.pdf', '2.1 MB', 'PDF', 'course'),
(1, 1, 4, 'Flowcharts and pseudocode', 'flowcharts-and-pseudocode.pdf', 'uploads/materials/flowcharts-and-pseudocode.pdf', '980 KB', 'PDF', 'course');

INSERT INTO past_papers (course_id, module_id, uploaded_by, academic_year_id, semester_id, study_year, exam_type, title, file_path, file_name) VALUES
(1, 3, 3, 1, 2, '2nd Year', 'UE', 'Database Systems UE 2025', 'uploads/past-papers/database-ue-2025.pdf', 'database-ue-2025.pdf'),
(1, 2, 4, 2, 3, '2nd Year', 'Supplementary', 'Web Application Development Supplementary', 'uploads/past-papers/web-supp-2024.pdf', 'web-supp-2024.pdf');

INSERT INTO timetables (course_id, module_id, lecturer_id, semester_id, day_of_week, start_time, end_time, room, class_group) VALUES
(1, 1, 4, 1, 'Monday', '08:00:00', '10:00:00', 'Lab 2', 'OD23IT'),
(1, 3, 3, 2, 'Tuesday', '10:00:00', '12:00:00', 'Room B4', 'OD23IT'),
(1, 2, 4, 2, 'Wednesday', '13:00:00', '15:00:00', 'Lab 1', 'OD23IT');

INSERT INTO results (student_id, course_id, module_id, semester_id, academic_year_id, uploaded_by, ca_score, exam_score, total_score, grade, status, released_at) VALUES
(5, 1, 1, 1, 1, 4, 47, 32, 79, 'B+', 'pass', '2026-05-10 10:00:00'),
(5, 1, 3, 2, 1, 3, 44, 36, 80, 'A', 'pass', '2026-05-25 10:00:00');

INSERT INTO ca_results (id, student_id, course_id, module_id, academic_year_id, semester_id, class_group, total_ca, max_ca, lecturer_remarks) VALUES
(1, 5, 1, 1, 1, 1, 'OD23IT', 47, 60, 'Strong practical effort. Improve speed on test questions.'),
(2, 5, 1, 3, 1, 2, 'OD23IT', 44, 60, 'Good database design progress. Revise normalization edge cases.');

INSERT INTO assessment_items (ca_result_id, item_name, score, max_score, sort_order) VALUES
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

INSERT INTO announcements (course_id, module_id, department_id, created_by, audience, title, body, publish_at) VALUES
(1, NULL, NULL, 2, 'course', 'Semester 2 timetable updated', 'The Web Application Development practical session now runs on Wednesday afternoon in Lab 1.', '2026-05-28 08:00:00'),
(1, 3, NULL, 3, 'course', 'Database Systems tutorial sheet', 'A new normalization tutorial has been added under Materials for revision this week.', '2026-05-24 09:00:00'),
(NULL, NULL, NULL, 2, 'global', 'Registration approvals in progress', 'Pending course registrations are being reviewed by the academic office. Check your registration page for status updates.', '2026-05-22 14:20:00');

INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent) VALUES
(2, 'approved course registration', 'course_registrations', 1, '127.0.0.1', 'Seed data'),
(4, 'uploaded material php-forms-pdo.pdf', 'materials', 1, '127.0.0.1', 'Seed data');

INSERT INTO settings (setting_key, setting_value, description) VALUES
('portal_status', 'open', 'Controls whether students can sign in and use registration tools.'),
('registration_window', '2026-05-01 to 2026-06-15', 'Current course registration period displayed to admins.'),
('support_email', 'support@stuca.local', 'Academic portal support contact.');
