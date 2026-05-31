<?php

declare(strict_types=1);

$hashes = [
    'super' => '$2a$12$CdZLlhTSQUFGE3SE24knme78aQDLY/H.sRtSK4JQSGLHaJietvzwi',
    'admin' => '$2a$12$r3NzKlZcBpSkcUZ8QfQFH.nNSPdawk9TWsPlb3lQ8DNaT60xI7k8m',
    'lecturer' => '$2a$12$JSAfJPfYRu5Zwtyu00KUzehiQ49U0kV0zZsfxoWHkTNma2z/mC1/6',
    'student' => '$2a$12$vrmZAzpBfB1q.UU51iQufORmXpwbg2YNbLMs/3ZFr7Yh9MNzOEz2W',
    'head' => '$2a$12$l9y5FY7F7U/jtgZ0VWvn4OxBHN797QhCkRjfwrnsw9H5aOxNcz9Uq',
];

return [
    'roles' => [
        ['id' => 1, 'slug' => 'super_admin', 'name' => 'Super Admin'],
        ['id' => 2, 'slug' => 'admin', 'name' => 'Admin'],
        ['id' => 3, 'slug' => 'department_head', 'name' => 'Department Head'],
        ['id' => 4, 'slug' => 'lecturer', 'name' => 'Lecturer'],
        ['id' => 5, 'slug' => 'student', 'name' => 'Student'],
    ],
    'departments' => [
        ['id' => 1, 'code' => 'SOC', 'name' => 'School of Computing', 'description' => 'Computing, software, networks, and data programmes.', 'head_name' => 'Dr. Rehema Said', 'user_count' => 4, 'course_count' => 2],
        ['id' => 2, 'code' => 'BUS', 'name' => 'Business Studies', 'description' => 'Business, accounting, procurement, and entrepreneurship.', 'head_name' => 'Dr. Elias Mboya', 'user_count' => 1, 'course_count' => 1],
    ],
    'users' => [
        ['id' => 1, 'department_id' => '', 'name' => 'Amina Kessy', 'email' => 'super@stuca.local', 'role' => 'super_admin', 'role_name' => 'Super Admin', 'status' => 'active', 'department_name' => 'Academic Registry', 'program' => 'System Administration', 'class_group' => 'HQ', 'student_number' => '', 'staff_number' => 'STF-001', 'phone' => '+255 712 440 118', 'password_hash' => $hashes['super'], 'created_at' => '2026-01-08 09:00:00'],
        ['id' => 2, 'department_id' => '', 'name' => 'Brian Mwakalinga', 'email' => 'admin@stuca.local', 'role' => 'admin', 'role_name' => 'Admin', 'status' => 'active', 'department_name' => 'Academic Registry', 'program' => 'Academic Operations', 'class_group' => 'Registry', 'student_number' => '', 'staff_number' => 'STF-018', 'phone' => '+255 713 918 204', 'password_hash' => $hashes['admin'], 'created_at' => '2026-01-10 09:00:00'],
        ['id' => 3, 'department_id' => 1, 'name' => 'Dr. Rehema Said', 'email' => 'head@stuca.local', 'role' => 'department_head', 'role_name' => 'Department Head', 'status' => 'active', 'department_name' => 'School of Computing', 'program' => 'School of Computing', 'class_group' => 'SOC', 'student_number' => '', 'staff_number' => 'STF-023', 'phone' => '+255 755 602 331', 'password_hash' => $hashes['head'], 'created_at' => '2026-01-11 09:00:00'],
        ['id' => 4, 'department_id' => 1, 'name' => 'Mr. Joseph Nkya', 'email' => 'lecturer@stuca.local', 'role' => 'lecturer', 'role_name' => 'Lecturer', 'status' => 'active', 'department_name' => 'School of Computing', 'program' => 'Software Engineering', 'class_group' => 'Lecturer', 'student_number' => '', 'staff_number' => 'STF-041', 'phone' => '+255 744 290 812', 'password_hash' => $hashes['lecturer'], 'created_at' => '2026-01-12 09:00:00'],
        ['id' => 5, 'department_id' => 1, 'name' => 'Neema Baraka', 'email' => 'student@stuca.local', 'role' => 'student', 'role_name' => 'Student', 'status' => 'active', 'department_name' => 'School of Computing', 'program' => 'Diploma in Information Technology', 'class_group' => 'OD23IT', 'student_number' => 'OD23IT-014', 'staff_number' => '', 'phone' => '+255 768 501 726', 'password_hash' => $hashes['student'], 'created_at' => '2026-01-20 09:00:00'],
    ],
    'academic_years' => [
        ['id' => 1, 'name' => '2025/26'],
        ['id' => 2, 'name' => '2024/25'],
    ],
    'semesters' => [
        ['id' => 1, 'name' => 'Semester 1', 'academic_year' => '2025/26'],
        ['id' => 2, 'name' => 'Semester 2', 'academic_year' => '2025/26'],
    ],
    'courses' => [
        ['id' => 1, 'department_id' => 1, 'code' => 'DIT201', 'title' => 'Diploma in Information Technology', 'description' => 'Practical software, database, networking, and support skills.', 'level' => 'Diploma', 'credits' => 120, 'status' => 'active', 'department_name' => 'School of Computing', 'module_count' => 4, 'student_count' => 2],
        ['id' => 2, 'department_id' => 1, 'code' => 'BCS301', 'title' => 'Bachelor of Computer Science', 'description' => 'Computer science foundations, systems, and software design.', 'level' => 'Degree', 'credits' => 360, 'status' => 'active', 'department_name' => 'School of Computing', 'module_count' => 2, 'student_count' => 1],
        ['id' => 3, 'department_id' => 2, 'code' => 'DBA110', 'title' => 'Diploma in Business Administration', 'description' => 'Operations, communication, accounting, and office systems.', 'level' => 'Diploma', 'credits' => 120, 'status' => 'active', 'department_name' => 'Business Studies', 'module_count' => 1, 'student_count' => 1],
    ],
    'modules' => [
        ['id' => 1, 'course_id' => 1, 'code' => 'ITP101', 'module_name' => 'Programming Fundamentals', 'title' => 'Programming Fundamentals', 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'department_name' => 'School of Computing', 'lecturer' => 'Mr. Joseph Nkya', 'semester' => 'Semester 1', 'credits' => 4, 'ca_score' => 0, 'ue_score' => 0, 'description' => 'Problem solving, variables, control flow, and structured programming.'],
        ['id' => 2, 'course_id' => 1, 'code' => 'WEB204', 'module_name' => 'Web Application Development', 'title' => 'Web Application Development', 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'department_name' => 'School of Computing', 'lecturer' => 'Mr. Joseph Nkya', 'semester' => 'Semester 2', 'credits' => 4, 'ca_score' => 0, 'ue_score' => 0, 'description' => 'HTML, CSS, JavaScript, PHP, and database-backed web applications.'],
        ['id' => 3, 'course_id' => 1, 'code' => 'DBS202', 'module_name' => 'Database Systems', 'title' => 'Database Systems', 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'department_name' => 'School of Computing', 'lecturer' => 'Dr. Rehema Said', 'semester' => 'Semester 2', 'credits' => 4, 'ca_score' => 0, 'ue_score' => 0, 'description' => 'Relational design, normalization, SQL, and transactions.'],
        ['id' => 4, 'course_id' => 1, 'code' => 'NET205', 'module_name' => 'Computer Networks', 'title' => 'Computer Networks', 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'department_name' => 'School of Computing', 'lecturer' => 'Mr. Joseph Nkya', 'semester' => 'Semester 1', 'credits' => 3, 'ca_score' => 0, 'ue_score' => 0, 'description' => 'Network models, switching, routing, and campus network services.'],
    ],
    'lecturer_courses' => [
        ['lecturer_id' => 4, 'course_id' => 1],
        ['lecturer_id' => 3, 'course_id' => 1],
    ],
    'registrations' => [
        ['id' => 1, 'user_id' => 5, 'student_name' => 'Neema Baraka', 'student_email' => 'student@stuca.local', 'course_id' => 1, 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'academic_year' => '2025/26', 'semester' => 'Semester 2', 'status' => 'approved', 'notes' => 'Continuing student', 'requested_at' => '2026-02-01 08:12:00', 'reviewed_by_name' => 'Brian Mwakalinga'],
        ['id' => 2, 'user_id' => 5, 'student_name' => 'Neema Baraka', 'student_email' => 'student@stuca.local', 'course_id' => 2, 'course_title' => 'Bachelor of Computer Science', 'course_code' => 'BCS301', 'academic_year' => '2025/26', 'semester' => 'Semester 2', 'status' => 'pending', 'notes' => 'Requested transfer evaluation', 'requested_at' => '2026-05-12 13:41:00', 'reviewed_by_name' => ''],
    ],
    'assignments' => [
        ['id' => 1, 'course_id' => 1, 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'module_name' => 'Database Systems', 'module_code' => 'DBS202', 'lecturer' => 'Dr. Rehema Said', 'title' => 'Normalized schema design', 'instructions' => 'Design an ERD and submit SQL create statements.', 'deadline' => '2026-06-14', 'submission_type' => 'Online upload', 'status' => 'open', 'semester' => 'Semester 2', 'file_path' => ''],
        ['id' => 2, 'course_id' => 1, 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'module_name' => 'Web Application Development', 'module_code' => 'WEB204', 'lecturer' => 'Mr. Joseph Nkya', 'title' => 'PHP dashboard project', 'instructions' => 'Build a secure CRUD dashboard with role checks.', 'deadline' => '2026-06-21', 'submission_type' => 'Online upload', 'status' => 'open', 'semester' => 'Semester 2', 'file_path' => ''],
        ['id' => 3, 'course_id' => 1, 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'module_name' => 'Programming Fundamentals', 'module_code' => 'ITP101', 'lecturer' => 'Mr. Joseph Nkya', 'title' => 'Algorithm practice set', 'instructions' => 'Solve ten array and loop exercises.', 'deadline' => '2026-06-07', 'submission_type' => 'Hard copy', 'status' => 'closed', 'semester' => 'Semester 1', 'file_path' => ''],
    ],
    'materials' => [
        ['id' => 1, 'course_id' => 1, 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'module_name' => 'Web Application Development', 'module_code' => 'WEB204', 'title' => 'PHP forms and PDO', 'file_name' => 'php-forms-pdo.pdf', 'file_path' => 'uploads/materials/php-forms-pdo.pdf', 'file_size' => '1.8 MB', 'file_type' => 'PDF', 'date_uploaded' => '2026-05-26 10:14:00', 'uploaded_by_name' => 'Mr. Joseph Nkya'],
        ['id' => 2, 'course_id' => 1, 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'module_name' => 'Database Systems', 'module_code' => 'DBS202', 'title' => 'Normalization tutorial', 'file_name' => 'normalization-tutorial.pdf', 'file_path' => 'uploads/materials/normalization-tutorial.pdf', 'file_size' => '2.1 MB', 'file_type' => 'PDF', 'date_uploaded' => '2026-05-24 09:30:00', 'uploaded_by_name' => 'Dr. Rehema Said'],
        ['id' => 3, 'course_id' => 1, 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'module_name' => 'Programming Fundamentals', 'module_code' => 'ITP101', 'title' => 'Flowcharts and pseudocode', 'file_name' => 'flowcharts-and-pseudocode.pdf', 'file_path' => 'uploads/materials/flowcharts-and-pseudocode.pdf', 'file_size' => '980 KB', 'file_type' => 'PDF', 'date_uploaded' => '2026-05-18 11:00:00', 'uploaded_by_name' => 'Mr. Joseph Nkya'],
    ],
    'past_papers' => [
        ['id' => 1, 'course_id' => 1, 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'module_name' => 'Database Systems', 'module_code' => 'DBS202', 'academic_year' => '2025/26', 'study_year' => '2nd Year', 'semester' => 'Semester 2', 'exam_type' => 'UE', 'title' => 'Database Systems UE 2025', 'file_path' => 'uploads/past-papers/database-ue-2025.pdf', 'file_name' => 'database-ue-2025.pdf'],
        ['id' => 2, 'course_id' => 1, 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'module_name' => 'Web Application Development', 'module_code' => 'WEB204', 'academic_year' => '2024/25', 'study_year' => '2nd Year', 'semester' => 'Semester 2', 'exam_type' => 'Supplementary', 'title' => 'Web Application Development Supplementary', 'file_path' => 'uploads/past-papers/web-supp-2024.pdf', 'file_name' => 'web-supp-2024.pdf'],
    ],
    'results' => [
        ['id' => 1, 'student_id' => 5, 'student_name' => 'Neema Baraka', 'course_id' => 1, 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'module_name' => 'Programming Fundamentals', 'module_code' => 'ITP101', 'academic_year' => '2025/26', 'semester' => 'Semester 1', 'ca_score' => 47, 'ue_score' => 32, 'total' => 79, 'grade' => 'B+', 'status' => 'pass'],
        ['id' => 2, 'student_id' => 5, 'student_name' => 'Neema Baraka', 'course_id' => 1, 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'module_name' => 'Database Systems', 'module_code' => 'DBS202', 'academic_year' => '2025/26', 'semester' => 'Semester 2', 'ca_score' => 44, 'ue_score' => 36, 'total' => 80, 'grade' => 'A', 'status' => 'pass'],
    ],
    'timetable' => [
        ['id' => 1, 'course_id' => 1, 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'day' => 'Monday', 'time' => '08:00 - 10:00', 'module_name' => 'Programming Fundamentals', 'module_code' => 'ITP101', 'lecturer' => 'Mr. Joseph Nkya', 'room' => 'Lab 2', 'class_group' => 'OD23IT'],
        ['id' => 2, 'course_id' => 1, 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'day' => 'Tuesday', 'time' => '10:00 - 12:00', 'module_name' => 'Database Systems', 'module_code' => 'DBS202', 'lecturer' => 'Dr. Rehema Said', 'room' => 'Room B4', 'class_group' => 'OD23IT'],
        ['id' => 3, 'course_id' => 1, 'course_title' => 'Diploma in Information Technology', 'course_code' => 'DIT201', 'day' => 'Wednesday', 'time' => '13:00 - 15:00', 'module_name' => 'Web Application Development', 'module_code' => 'WEB204', 'lecturer' => 'Mr. Joseph Nkya', 'room' => 'Lab 1', 'class_group' => 'OD23IT'],
    ],
    'announcements' => [
        ['id' => 1, 'title' => 'Semester 2 timetable updated', 'body' => 'The Web Application Development practical session now runs on Wednesday afternoon in Lab 1.', 'author' => 'Academic Office', 'date_posted' => '2026-05-28 08:00:00', 'class_group' => 'DIT201', 'audience' => 'course', 'course_title' => 'Diploma in Information Technology'],
        ['id' => 2, 'title' => 'Database Systems tutorial sheet', 'body' => 'A new normalization tutorial has been added under Materials for revision this week.', 'author' => 'Dr. Rehema Said', 'date_posted' => '2026-05-24 09:00:00', 'class_group' => 'DIT201', 'audience' => 'course', 'course_title' => 'Diploma in Information Technology'],
        ['id' => 3, 'title' => 'Registration approvals in progress', 'body' => 'Pending course registrations are being reviewed by the academic office. Check your registration page for status updates.', 'author' => 'Brian Mwakalinga', 'date_posted' => '2026-05-22 14:20:00', 'class_group' => 'Global', 'audience' => 'global', 'course_title' => ''],
    ],
    'activity_logs' => [
        ['id' => 1, 'user_name' => 'Brian Mwakalinga', 'user_email' => 'admin@stuca.local', 'action' => 'approved course registration', 'entity_type' => 'course_registrations', 'ip_address' => '127.0.0.1', 'created_at' => '2026-05-28 09:30:00'],
        ['id' => 2, 'user_name' => 'Mr. Joseph Nkya', 'user_email' => 'lecturer@stuca.local', 'action' => 'uploaded material php-forms-pdo.pdf', 'entity_type' => 'materials', 'ip_address' => '127.0.0.1', 'created_at' => '2026-05-26 10:14:00'],
    ],
    'settings' => [
        ['id' => 1, 'setting_key' => 'portal_status', 'setting_value' => 'open', 'description' => 'Controls whether students can sign in and use registration tools.'],
        ['id' => 2, 'setting_key' => 'registration_window', 'setting_value' => '2026-05-01 to 2026-06-15', 'description' => 'Current course registration period displayed to admins.'],
        ['id' => 3, 'setting_key' => 'support_email', 'setting_value' => 'support@stuca.local', 'description' => 'Academic portal support contact.'],
    ],
];
