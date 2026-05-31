<?php

declare(strict_types=1);

final class DatabaseRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public static function connect(array $config): self
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $pdo->query('SELECT 1');
        $pdo->query('SELECT COUNT(*) FROM roles');

        return new self($pdo);
    }

    public function findUserByEmail(string $email): ?array
    {
        return $this->fetchOne(
            'SELECT u.*, r.slug AS role, r.name AS role_name, d.name AS department_name, d.code AS department_code
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.email = :email
             LIMIT 1',
            ['email' => $email]
        );
    }

    public function studentNumberExists(string $studentNumber): bool
    {
        if (trim($studentNumber) === '') {
            return false;
        }

        return $this->fetchOne(
            'SELECT id FROM users WHERE student_number = :student_number LIMIT 1',
            ['student_number' => $studentNumber]
        ) !== null;
    }

    public function getStats(array $user): array
    {
        $role = $user['role'] ?? 'student';
        $stats = [
            'total_students' => $this->count('SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = "student"'),
            'total_lecturers' => $this->count('SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = "lecturer"'),
            'total_courses' => $this->count('SELECT COUNT(*) FROM courses'),
            'total_departments' => $this->count('SELECT COUNT(*) FROM departments'),
            'total_assignments' => $this->count('SELECT COUNT(*) FROM assignments'),
            'total_announcements' => $this->count('SELECT COUNT(*) FROM announcements'),
            'pending_registrations' => $this->count('SELECT COUNT(*) FROM course_registrations WHERE status = "pending"'),
            'materials' => $this->count('SELECT COUNT(*) FROM materials'),
            'past_papers' => $this->count('SELECT COUNT(*) FROM past_papers'),
            'modules' => $this->count('SELECT COUNT(*) FROM modules'),
            'activity_logs' => $this->count('SELECT COUNT(*) FROM activity_logs'),
            'average' => $this->averageForUser($user),
        ];

        if ($role === 'student') {
            $stats['registered_courses'] = $this->count(
                'SELECT COUNT(*) FROM course_registrations WHERE user_id = :user_id AND status = "approved"',
                ['user_id' => $user['id']]
            );
        }

        if ($role === 'lecturer') {
            $stats['assigned_courses'] = $this->count(
                'SELECT COUNT(*) FROM lecturer_courses WHERE lecturer_id = :user_id',
                ['user_id' => $user['id']]
            );
            $stats['enrolled_students'] = $this->count(
                'SELECT COUNT(DISTINCT cr.user_id)
                 FROM course_registrations cr
                 JOIN lecturer_courses lc ON lc.course_id = cr.course_id
                 WHERE lc.lecturer_id = :user_id AND cr.status = "approved"',
                ['user_id' => $user['id']]
            );
        }

        return $stats;
    }

    public function getUsers(array $filters = []): array
    {
        $sql = 'SELECT u.id, u.name, u.email, u.status, u.program, u.class_group, u.student_number, u.staff_number,
                       u.phone, u.created_at, r.slug AS role, r.name AS role_name, d.name AS department_name
                FROM users u
                JOIN roles r ON r.id = u.role_id
                LEFT JOIN departments d ON d.id = u.department_id';

        return $this->queryWithFilters(
            $sql,
            $filters,
            ['role' => 'r.slug', 'status' => 'u.status', 'department_id' => 'u.department_id'],
            ['u.name', 'u.email', 'u.program', 'u.class_group', 'r.name', 'd.name'],
            'u.created_at DESC'
        );
    }

    public function getStudents(array $filters = []): array
    {
        $filters['role'] = 'student';

        return $this->getUsers($filters);
    }

    public function getLecturers(array $filters = []): array
    {
        $filters['role'] = 'lecturer';

        return $this->getUsers($filters);
    }

    public function getDepartments(array $filters = []): array
    {
        $sql = 'SELECT d.*, h.name AS head_name,
                       (SELECT COUNT(*) FROM users u WHERE u.department_id = d.id) AS user_count,
                       (SELECT COUNT(*) FROM courses c WHERE c.department_id = d.id) AS course_count
                FROM departments d
                LEFT JOIN users h ON h.id = d.head_user_id';

        return $this->queryWithFilters($sql, $filters, [], ['d.name', 'd.code', 'd.description', 'h.name'], 'd.name');
    }

    public function getCourses(array $filters = []): array
    {
        $sql = 'SELECT c.*, d.name AS department_name,
                       (SELECT COUNT(*) FROM modules m WHERE m.course_id = c.id) AS module_count,
                       (SELECT COUNT(*) FROM course_registrations cr WHERE cr.course_id = c.id AND cr.status = "approved") AS student_count
                FROM courses c
                LEFT JOIN departments d ON d.id = c.department_id';

        return $this->queryWithFilters(
            $sql,
            $filters,
            ['department_id' => 'c.department_id', 'status' => 'c.status'],
            ['c.title', 'c.code', 'c.level', 'd.name', 'c.description'],
            'c.title'
        );
    }

    public function getModules(array $filters = []): array
    {
        $sql = 'SELECT m.id, m.code, m.title AS module_name, m.description, m.credits,
                       c.title AS course_title, c.code AS course_code, d.name AS department_name,
                       s.name AS semester,
                       COALESCE((SELECT GROUP_CONCAT(u.name SEPARATOR ", ")
                                 FROM lecturer_courses lc
                                 JOIN users u ON u.id = lc.lecturer_id
                                 WHERE lc.course_id = m.course_id), "Unassigned") AS lecturer,
                       0 AS ca_score, 0 AS ue_score
                FROM modules m
                JOIN courses c ON c.id = m.course_id
                LEFT JOIN departments d ON d.id = c.department_id
                LEFT JOIN semesters s ON s.id = m.semester_id';

        return $this->queryWithFilters(
            $sql,
            $filters,
            ['course_id' => 'm.course_id', 'semester' => 's.name'],
            ['m.title', 'm.code', 'c.title', 'c.code', 'd.name', 'm.description'],
            'c.title, m.title'
        );
    }

    public function getAssignments(array $filters = [], ?array $user = null): array
    {
        $sql = 'SELECT a.id, a.title, a.instructions, a.deadline, a.submission_type, a.status, a.file_path,
                       c.title AS course_title, c.code AS course_code,
                       m.title AS module_name, m.code AS module_code,
                       u.name AS lecturer, s.name AS semester
                FROM assignments a
                JOIN courses c ON c.id = a.course_id
                LEFT JOIN modules m ON m.id = a.module_id
                LEFT JOIN users u ON u.id = a.lecturer_id
                LEFT JOIN semesters s ON s.id = a.semester_id';

        [$scopeSql, $scopeParams] = $this->scopeAssignmentsForUser($user);
        $filters['_where'] = $scopeSql;
        $filters['_params'] = $scopeParams;

        return $this->queryWithFilters(
            $sql,
            $filters,
            ['module_name' => 'm.title', 'course_id' => 'a.course_id', 'semester' => 's.name', 'submission_type' => 'a.submission_type', 'status' => 'a.status'],
            ['a.title', 'm.title', 'm.code', 'c.title', 'c.code', 'u.name', 'a.instructions'],
            'a.deadline'
        );
    }

    public function getMaterials(array $filters = [], ?array $user = null): array
    {
        $sql = 'SELECT mat.id, mat.title, mat.file_name, mat.file_path, mat.file_size, mat.file_type, mat.created_at AS date_uploaded,
                       c.title AS course_title, c.code AS course_code,
                       m.title AS module_name, m.code AS module_code,
                       u.name AS uploaded_by_name
                FROM materials mat
                JOIN courses c ON c.id = mat.course_id
                LEFT JOIN modules m ON m.id = mat.module_id
                LEFT JOIN users u ON u.id = mat.uploaded_by';

        [$scopeSql, $scopeParams] = $this->scopeCourseRecordsForUser('mat.course_id', $user);
        $filters['_where'] = $scopeSql;
        $filters['_params'] = $scopeParams;

        return $this->queryWithFilters(
            $sql,
            $filters,
            ['module_name' => 'm.title', 'course_id' => 'mat.course_id', 'file_type' => 'mat.file_type'],
            ['mat.title', 'mat.file_name', 'm.title', 'm.code', 'c.title', 'c.code', 'u.name'],
            'mat.created_at DESC'
        );
    }

    public function getPastPapers(array $filters = [], ?array $user = null): array
    {
        $sql = 'SELECT pp.id, pp.title, pp.study_year, pp.exam_type, pp.file_path, pp.file_name,
                       ay.name AS academic_year, s.name AS semester,
                       c.title AS course_title, c.code AS course_code,
                       m.title AS module_name, m.code AS module_code
                FROM past_papers pp
                JOIN courses c ON c.id = pp.course_id
                LEFT JOIN modules m ON m.id = pp.module_id
                LEFT JOIN academic_years ay ON ay.id = pp.academic_year_id
                LEFT JOIN semesters s ON s.id = pp.semester_id';

        [$scopeSql, $scopeParams] = $this->scopeCourseRecordsForUser('pp.course_id', $user);
        $filters['_where'] = $scopeSql;
        $filters['_params'] = $scopeParams;

        return $this->queryWithFilters(
            $sql,
            $filters,
            ['academic_year' => 'ay.name', 'study_year' => 'pp.study_year', 'semester' => 's.name', 'exam_type' => 'pp.exam_type', 'course_id' => 'pp.course_id'],
            ['pp.title', 'm.title', 'm.code', 'c.title', 'c.code', 'pp.exam_type'],
            'ay.name DESC, c.title, pp.exam_type'
        );
    }

    public function getResults(array $filters = [], ?array $user = null): array
    {
        $sql = 'SELECT r.id, r.ca_score, r.exam_score AS ue_score, r.total_score AS total, r.grade, r.status, r.released_at,
                       student.name AS student_name, ay.name AS academic_year, s.name AS semester,
                       c.title AS course_title, c.code AS course_code,
                       m.title AS module_name, m.code AS module_code
                FROM results r
                JOIN users student ON student.id = r.student_id
                JOIN courses c ON c.id = r.course_id
                LEFT JOIN modules m ON m.id = r.module_id
                LEFT JOIN semesters s ON s.id = r.semester_id
                LEFT JOIN academic_years ay ON ay.id = r.academic_year_id';

        [$scopeSql, $scopeParams] = $this->scopeResultsForUser($user);
        $filters['_where'] = $scopeSql;
        $filters['_params'] = $scopeParams;

        return $this->queryWithFilters(
            $sql,
            $filters,
            ['semester' => 's.name', 'status' => 'r.status', 'course_id' => 'r.course_id'],
            ['student.name', 'm.title', 'm.code', 'c.title', 'c.code', 'r.grade'],
            'student.name, c.title'
        );
    }

    public function getTimetable(array $filters = [], ?array $user = null): array
    {
        $sql = 'SELECT t.id, t.day_of_week AS day, TIME_FORMAT(t.start_time, "%H:%i") AS starts_at,
                       TIME_FORMAT(t.end_time, "%H:%i") AS ends_at,
                       CONCAT(TIME_FORMAT(t.start_time, "%H:%i"), " - ", TIME_FORMAT(t.end_time, "%H:%i")) AS time,
                       t.room, t.class_group,
                       c.title AS course_title, c.code AS course_code,
                       m.title AS module_name, m.code AS module_code,
                       u.name AS lecturer, s.name AS semester
                FROM timetables t
                JOIN courses c ON c.id = t.course_id
                LEFT JOIN modules m ON m.id = t.module_id
                LEFT JOIN users u ON u.id = t.lecturer_id
                LEFT JOIN semesters s ON s.id = t.semester_id';

        [$scopeSql, $scopeParams] = $this->scopeCourseRecordsForUser('t.course_id', $user);
        $filters['_where'] = $scopeSql;
        $filters['_params'] = $scopeParams;

        return $this->queryWithFilters(
            $sql,
            $filters,
            ['day' => 't.day_of_week', 'course_id' => 't.course_id'],
            ['m.title', 'm.code', 'c.title', 'c.code', 'u.name', 't.room', 't.class_group'],
            'FIELD(t.day_of_week, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"), t.start_time'
        );
    }

    public function getAnnouncements(array $filters = [], ?array $user = null): array
    {
        $sql = 'SELECT a.id, a.title, a.body, a.audience, a.publish_at AS date_posted,
                       creator.name AS author, d.name AS department_name, c.title AS course_title,
                       COALESCE(c.code, d.code, "Global") AS class_group
                FROM announcements a
                LEFT JOIN users creator ON creator.id = a.created_by
                LEFT JOIN departments d ON d.id = a.department_id
                LEFT JOIN courses c ON c.id = a.course_id';

        [$scopeSql, $scopeParams] = $this->scopeAnnouncementsForUser($user);
        $filters['_where'] = $scopeSql;
        $filters['_params'] = $scopeParams;

        return $this->queryWithFilters(
            $sql,
            $filters,
            ['class_group' => 'COALESCE(c.code, d.code, "Global")', 'audience' => 'a.audience'],
            ['a.title', 'a.body', 'creator.name', 'd.name', 'c.title', 'c.code'],
            'a.publish_at DESC'
        );
    }

    public function getCourseRegistrations(array $filters = [], ?array $user = null): array
    {
        $sql = 'SELECT cr.*, student.name AS student_name, student.email AS student_email,
                       c.title AS course_title, c.code AS course_code,
                       ay.name AS academic_year, s.name AS semester, reviewer.name AS reviewed_by_name
                FROM course_registrations cr
                JOIN users student ON student.id = cr.user_id
                JOIN courses c ON c.id = cr.course_id
                LEFT JOIN academic_years ay ON ay.id = cr.academic_year_id
                LEFT JOIN semesters s ON s.id = cr.semester_id
                LEFT JOIN users reviewer ON reviewer.id = cr.reviewed_by';

        [$scopeSql, $scopeParams] = $this->scopeRegistrationsForUser($user);
        $filters['_where'] = $scopeSql;
        $filters['_params'] = $scopeParams;

        return $this->queryWithFilters(
            $sql,
            $filters,
            ['status' => 'cr.status', 'course_id' => 'cr.course_id'],
            ['student.name', 'student.email', 'c.title', 'c.code', 'cr.notes'],
            'cr.requested_at DESC'
        );
    }

    public function getActivityLogs(array $filters = []): array
    {
        $sql = 'SELECT al.*, u.name AS user_name, u.email AS user_email
                FROM activity_logs al
                LEFT JOIN users u ON u.id = al.user_id';

        return $this->queryWithFilters(
            $sql,
            $filters,
            ['entity_type' => 'al.entity_type'],
            ['u.name', 'u.email', 'al.action', 'al.entity_type', 'al.ip_address'],
            'al.created_at DESC'
        );
    }

    public function getSettings(array $filters = []): array
    {
        return $this->queryWithFilters(
            'SELECT * FROM settings',
            $filters,
            [],
            ['setting_key', 'setting_value', 'description'],
            'setting_key'
        );
    }

    public function getReports(): array
    {
        return [
            'students_per_course' => $this->fetchAll(
                'SELECT c.code, c.title, COUNT(cr.user_id) AS total
                 FROM courses c
                 LEFT JOIN course_registrations cr ON cr.course_id = c.id AND cr.status = "approved"
                 GROUP BY c.id, c.code, c.title
                 ORDER BY total DESC, c.title'
            ),
            'lecturers_per_department' => $this->fetchAll(
                'SELECT d.code, d.name AS title, COUNT(r.id) AS total
                 FROM departments d
                 LEFT JOIN users u ON u.department_id = d.id
                 LEFT JOIN roles r ON r.id = u.role_id AND r.slug = "lecturer"
                 GROUP BY d.id, d.code, d.name
                 ORDER BY total DESC, d.name'
            ),
            'assignments_per_course' => $this->fetchAll(
                'SELECT c.code, c.title, COUNT(a.id) AS total
                 FROM courses c
                 LEFT JOIN assignments a ON a.course_id = c.id
                 GROUP BY c.id, c.code, c.title
                 ORDER BY total DESC, c.title'
            ),
            'registration_totals' => $this->fetchAll(
                'SELECT status AS title, COUNT(*) AS total
                 FROM course_registrations
                 GROUP BY status
                 ORDER BY total DESC'
            ),
            'results_summary' => $this->fetchAll(
                'SELECT grade AS title, COUNT(*) AS total, ROUND(AVG(total_score), 1) AS average_score
                 FROM results
                 GROUP BY grade
                 ORDER BY average_score DESC'
            ),
        ];
    }

    public function getRoleOptions(): array
    {
        return $this->fetchAll('SELECT id, slug, name FROM roles ORDER BY id');
    }

    public function getDepartmentOptions(): array
    {
        return $this->fetchAll('SELECT id, code, name FROM departments ORDER BY name');
    }

    public function getCourseOptions(): array
    {
        return $this->fetchAll('SELECT id, code, title FROM courses ORDER BY title');
    }

    public function getModuleOptions(): array
    {
        return $this->fetchAll('SELECT id, code, title, course_id FROM modules ORDER BY title');
    }

    public function getAcademicYearOptions(): array
    {
        return $this->fetchAll('SELECT id, name FROM academic_years ORDER BY starts_on DESC');
    }

    public function getSemesterOptions(): array
    {
        return $this->fetchAll('SELECT s.id, s.name, ay.name AS academic_year FROM semesters s JOIN academic_years ay ON ay.id = s.academic_year_id ORDER BY ay.starts_on DESC, s.starts_on');
    }

    public function getLecturerOptions(): array
    {
        return $this->fetchAll(
            'SELECT u.id, u.name, u.email
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE r.slug IN ("lecturer", "department_head")
             ORDER BY u.name'
        );
    }

    public function getStudentOptions(): array
    {
        return $this->fetchAll(
            'SELECT u.id, u.name, u.email
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE r.slug = "student"
             ORDER BY u.name'
        );
    }

    public function createUser(array $data, ?array $actor = null): void
    {
        $roleId = $this->roleId($data['role'] ?? 'student');
        $statement = $this->pdo->prepare(
            'INSERT INTO users (role_id, department_id, name, email, password_hash, status, student_number, staff_number, phone, program, class_group)
             VALUES (:role_id, :department_id, :name, :email, :password_hash, :status, :student_number, :staff_number, :phone, :program, :class_group)'
        );
        $statement->execute([
            'role_id' => $roleId,
            'department_id' => $this->nullIfEmpty($data['department_id'] ?? null),
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'status' => $data['status'] ?? 'active',
            'student_number' => $this->nullIfEmpty($data['student_number'] ?? null),
            'staff_number' => $this->nullIfEmpty($data['staff_number'] ?? null),
            'phone' => $this->nullIfEmpty($data['phone'] ?? null),
            'program' => $this->nullIfEmpty($data['program'] ?? null),
            'class_group' => $this->nullIfEmpty($data['class_group'] ?? null),
        ]);

        $this->logActivity($actor, 'created user ' . $data['email'], 'users', (int) $this->pdo->lastInsertId());
    }

    public function updateUserStatus(int $id, string $status, ?array $actor = null): void
    {
        $this->execute('UPDATE users SET status = :status WHERE id = :id', ['status' => $status, 'id' => $id]);
        $this->logActivity($actor, 'updated user status to ' . $status, 'users', $id);
    }

    public function deleteUser(int $id, ?array $actor = null): void
    {
        $this->execute('DELETE FROM users WHERE id = :id', ['id' => $id]);
        $this->logActivity($actor, 'deleted user', 'users', $id);
    }

    public function createDepartment(array $data, ?array $actor = null): void
    {
        $this->execute(
            'INSERT INTO departments (name, code, description, head_user_id) VALUES (:name, :code, :description, :head_user_id)',
            [
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'description' => $this->nullIfEmpty($data['description'] ?? null),
                'head_user_id' => $this->nullIfEmpty($data['head_user_id'] ?? null),
            ]
        );
        $this->logActivity($actor, 'created department ' . $data['code'], 'departments', (int) $this->pdo->lastInsertId());
    }

    public function createCourse(array $data, ?array $actor = null): void
    {
        $this->execute(
            'INSERT INTO courses (department_id, code, title, description, level, credits, status)
             VALUES (:department_id, :code, :title, :description, :level, :credits, :status)',
            [
                'department_id' => $this->nullIfEmpty($data['department_id'] ?? null),
                'code' => strtoupper($data['code']),
                'title' => $data['title'],
                'description' => $this->nullIfEmpty($data['description'] ?? null),
                'level' => $this->nullIfEmpty($data['level'] ?? null),
                'credits' => (int) ($data['credits'] ?? 3),
                'status' => $data['status'] ?? 'active',
            ]
        );
        $courseId = (int) $this->pdo->lastInsertId();
        if (!empty($data['lecturer_id'])) {
            $this->assignLecturerToCourse((int) $data['lecturer_id'], $courseId, $data);
        }
        $this->logActivity($actor, 'created course ' . $data['code'], 'courses', $courseId);
    }

    public function createModule(array $data, ?array $actor = null): void
    {
        $this->execute(
            'INSERT INTO modules (course_id, semester_id, code, title, description, credits)
             VALUES (:course_id, :semester_id, :code, :title, :description, :credits)',
            [
                'course_id' => (int) $data['course_id'],
                'semester_id' => $this->nullIfEmpty($data['semester_id'] ?? null),
                'code' => strtoupper($data['code']),
                'title' => $data['title'],
                'description' => $this->nullIfEmpty($data['description'] ?? null),
                'credits' => (int) ($data['credits'] ?? 3),
            ]
        );
        $this->logActivity($actor, 'created module ' . $data['code'], 'modules', (int) $this->pdo->lastInsertId());
    }

    public function createRegistration(int $studentId, array $data, ?array $actor = null): void
    {
        $this->execute(
            'INSERT INTO course_registrations (user_id, course_id, academic_year_id, semester_id, status, notes)
             VALUES (:user_id, :course_id, :academic_year_id, :semester_id, "pending", :notes)
             ON DUPLICATE KEY UPDATE status = "pending", notes = VALUES(notes), updated_at = CURRENT_TIMESTAMP',
            [
                'user_id' => $studentId,
                'course_id' => (int) $data['course_id'],
                'academic_year_id' => (int) $data['academic_year_id'],
                'semester_id' => (int) $data['semester_id'],
                'notes' => $this->nullIfEmpty($data['notes'] ?? null),
            ]
        );
        $this->logActivity($actor, 'requested course registration', 'course_registrations', null);
    }

    public function updateRegistrationStatus(int $id, string $status, ?array $actor = null): void
    {
        $this->execute(
            'UPDATE course_registrations
             SET status = :status, reviewed_by = :reviewed_by, reviewed_at = CURRENT_TIMESTAMP
             WHERE id = :id',
            [
                'status' => $status,
                'reviewed_by' => $actor['id'] ?? null,
                'id' => $id,
            ]
        );
        $this->logActivity($actor, 'set registration to ' . $status, 'course_registrations', $id);
    }

    public function createAssignment(array $data, ?array $actor = null): void
    {
        $this->execute(
            'INSERT INTO assignments (course_id, module_id, lecturer_id, semester_id, title, instructions, deadline, submission_type, file_path, status)
             VALUES (:course_id, :module_id, :lecturer_id, :semester_id, :title, :instructions, :deadline, :submission_type, :file_path, :status)',
            [
                'course_id' => (int) $data['course_id'],
                'module_id' => $this->nullIfEmpty($data['module_id'] ?? null),
                'lecturer_id' => $this->nullIfEmpty($data['lecturer_id'] ?? ($actor['id'] ?? null)),
                'semester_id' => $this->nullIfEmpty($data['semester_id'] ?? null),
                'title' => $data['title'],
                'instructions' => $this->nullIfEmpty($data['instructions'] ?? null),
                'deadline' => $data['deadline'],
                'submission_type' => $data['submission_type'] ?? 'Online upload',
                'file_path' => $this->nullIfEmpty($data['file_path'] ?? null),
                'status' => $data['status'] ?? 'open',
            ]
        );
        $this->logActivity($actor, 'created assignment ' . $data['title'], 'assignments', (int) $this->pdo->lastInsertId());
    }

    public function createAssignmentSubmission(array $data, ?array $actor = null): void
    {
        $this->execute(
            'INSERT INTO assignment_submissions (assignment_id, student_id, file_path, original_name, status)
             VALUES (:assignment_id, :student_id, :file_path, :original_name, "submitted")
             ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), original_name = VALUES(original_name), submitted_at = CURRENT_TIMESTAMP, status = "resubmitted"',
            [
                'assignment_id' => (int) $data['assignment_id'],
                'student_id' => (int) $data['student_id'],
                'file_path' => $data['file_path'],
                'original_name' => $data['original_name'],
            ]
        );
        $this->logActivity($actor, 'submitted assignment', 'assignment_submissions', null);
    }

    public function createMaterial(array $data, ?array $actor = null): void
    {
        $this->execute(
            'INSERT INTO materials (course_id, module_id, uploaded_by, title, file_name, file_path, file_size, file_type, visibility)
             VALUES (:course_id, :module_id, :uploaded_by, :title, :file_name, :file_path, :file_size, :file_type, :visibility)',
            [
                'course_id' => (int) $data['course_id'],
                'module_id' => $this->nullIfEmpty($data['module_id'] ?? null),
                'uploaded_by' => $actor['id'] ?? ($data['uploaded_by'] ?? null),
                'title' => $data['title'] ?? $data['file_name'],
                'file_name' => $data['file_name'],
                'file_path' => $data['file_path'],
                'file_size' => $data['file_size'],
                'file_type' => $data['file_type'],
                'visibility' => $data['visibility'] ?? 'course',
            ]
        );
        $this->logActivity($actor, 'uploaded material ' . $data['file_name'], 'materials', (int) $this->pdo->lastInsertId());
    }

    public function createPastPaper(array $data, ?array $actor = null): void
    {
        $this->execute(
            'INSERT INTO past_papers (course_id, module_id, uploaded_by, academic_year_id, semester_id, study_year, exam_type, title, file_path, file_name)
             VALUES (:course_id, :module_id, :uploaded_by, :academic_year_id, :semester_id, :study_year, :exam_type, :title, :file_path, :file_name)',
            [
                'course_id' => (int) $data['course_id'],
                'module_id' => $this->nullIfEmpty($data['module_id'] ?? null),
                'uploaded_by' => $actor['id'] ?? null,
                'academic_year_id' => (int) $data['academic_year_id'],
                'semester_id' => $this->nullIfEmpty($data['semester_id'] ?? null),
                'study_year' => $data['study_year'],
                'exam_type' => $data['exam_type'],
                'title' => $data['title'],
                'file_path' => $data['file_path'],
                'file_name' => $data['file_name'],
            ]
        );
        $this->logActivity($actor, 'uploaded past paper ' . $data['title'], 'past_papers', (int) $this->pdo->lastInsertId());
    }

    public function createTimetable(array $data, ?array $actor = null): void
    {
        $this->execute(
            'INSERT INTO timetables (course_id, module_id, lecturer_id, semester_id, day_of_week, start_time, end_time, room, class_group)
             VALUES (:course_id, :module_id, :lecturer_id, :semester_id, :day_of_week, :start_time, :end_time, :room, :class_group)',
            [
                'course_id' => (int) $data['course_id'],
                'module_id' => $this->nullIfEmpty($data['module_id'] ?? null),
                'lecturer_id' => $this->nullIfEmpty($data['lecturer_id'] ?? null),
                'semester_id' => $this->nullIfEmpty($data['semester_id'] ?? null),
                'day_of_week' => $data['day_of_week'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'room' => $data['room'],
                'class_group' => $this->nullIfEmpty($data['class_group'] ?? null),
            ]
        );
        $this->logActivity($actor, 'created timetable slot', 'timetables', (int) $this->pdo->lastInsertId());
    }

    public function createResult(array $data, ?array $actor = null): void
    {
        $ca = (int) $data['ca_score'];
        $exam = (int) $data['exam_score'];
        $total = min(100, $ca + $exam);

        $this->execute(
            'INSERT INTO results (student_id, course_id, module_id, semester_id, academic_year_id, uploaded_by, ca_score, exam_score, total_score, grade, status, released_at)
             VALUES (:student_id, :course_id, :module_id, :semester_id, :academic_year_id, :uploaded_by, :ca_score, :exam_score, :total_score, :grade, :status, CURRENT_TIMESTAMP)',
            [
                'student_id' => (int) $data['student_id'],
                'course_id' => (int) $data['course_id'],
                'module_id' => $this->nullIfEmpty($data['module_id'] ?? null),
                'semester_id' => $this->nullIfEmpty($data['semester_id'] ?? null),
                'academic_year_id' => $this->nullIfEmpty($data['academic_year_id'] ?? null),
                'uploaded_by' => $actor['id'] ?? null,
                'ca_score' => $ca,
                'exam_score' => $exam,
                'total_score' => $total,
                'grade' => $data['grade'] ?: $this->gradeForScore($total),
                'status' => $data['status'] ?: ($total >= 50 ? 'pass' : 'repeat'),
            ]
        );
        $this->logActivity($actor, 'uploaded result', 'results', (int) $this->pdo->lastInsertId());
    }

    public function createAnnouncement(array $data, ?array $actor = null): void
    {
        $this->execute(
            'INSERT INTO announcements (course_id, module_id, department_id, created_by, audience, title, body, publish_at)
             VALUES (:course_id, :module_id, :department_id, :created_by, :audience, :title, :body, :publish_at)',
            [
                'course_id' => $this->nullIfEmpty($data['course_id'] ?? null),
                'module_id' => $this->nullIfEmpty($data['module_id'] ?? null),
                'department_id' => $this->nullIfEmpty($data['department_id'] ?? null),
                'created_by' => $actor['id'] ?? null,
                'audience' => $data['audience'] ?? 'global',
                'title' => $data['title'],
                'body' => $data['body'],
                'publish_at' => $data['publish_at'] ?: date('Y-m-d H:i:s'),
            ]
        );
        $this->logActivity($actor, 'posted announcement ' . $data['title'], 'announcements', (int) $this->pdo->lastInsertId());
    }

    public function updateSetting(string $key, string $value, ?array $actor = null): void
    {
        $this->execute(
            'UPDATE settings SET setting_value = :setting_value WHERE setting_key = :setting_key',
            ['setting_value' => $value, 'setting_key' => $key]
        );
        $this->logActivity($actor, 'updated setting ' . $key, 'settings', null);
    }

    private function queryWithFilters(string $sql, array $filters, array $exactFields, array $searchFields, string $orderBy): array
    {
        $where = [];
        $params = $filters['_params'] ?? [];

        if (!empty($filters['_where'])) {
            $where[] = '(' . $filters['_where'] . ')';
        }

        foreach ($exactFields as $field => $column) {
            if (isset($filters[$field]) && trim((string) $filters[$field]) !== '') {
                $placeholder = preg_replace('/[^A-Za-z0-9_]/', '_', $field);
                $where[] = $column . ' = :' . $placeholder;
                $params[$placeholder] = $filters[$field];
            }
        }

        if (!empty($filters['search'])) {
            $searchParts = [];
            foreach ($searchFields as $index => $field) {
                $placeholder = 'search_' . $index;
                $searchParts[] = $field . ' LIKE :' . $placeholder;
                $params[$placeholder] = '%' . $filters['search'] . '%';
            }
            $where[] = '(' . implode(' OR ', $searchParts) . ')';
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY ' . $orderBy;

        return $this->fetchAll($sql, $params);
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    private function fetchOne(string $sql, array $params = []): ?array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return $row ?: null;
    }

    private function execute(string $sql, array $params = []): void
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
    }

    private function count(string $sql, array $params = []): int
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    private function roleId(string $slug): int
    {
        $role = $this->fetchOne('SELECT id FROM roles WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
        if (!$role) {
            throw new RuntimeException('Selected role was not found.');
        }

        return (int) $role['id'];
    }

    private function assignLecturerToCourse(int $lecturerId, int $courseId, array $data): void
    {
        $academicYearId = $data['academic_year_id'] ?? $this->fetchOne('SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1')['id'] ?? null;
        $semesterId = $data['semester_id'] ?? $this->fetchOne('SELECT id FROM semesters WHERE is_current = 1 LIMIT 1')['id'] ?? null;

        $this->execute(
            'INSERT IGNORE INTO lecturer_courses (lecturer_id, course_id, academic_year_id, semester_id)
             VALUES (:lecturer_id, :course_id, :academic_year_id, :semester_id)',
            [
                'lecturer_id' => $lecturerId,
                'course_id' => $courseId,
                'academic_year_id' => $academicYearId,
                'semester_id' => $semesterId,
            ]
        );
    }

    private function nullIfEmpty(mixed $value): mixed
    {
        return $value === '' || $value === null ? null : $value;
    }

    private function averageForUser(array $user): int
    {
        if (($user['role'] ?? '') !== 'student') {
            return (int) $this->count('SELECT COALESCE(ROUND(AVG(total_score)), 0) FROM results');
        }

        return (int) $this->count(
            'SELECT COALESCE(ROUND(AVG(total_score)), 0) FROM results WHERE student_id = :student_id',
            ['student_id' => $user['id']]
        );
    }

    private function gradeForScore(int $score): string
    {
        return match (true) {
            $score >= 80 => 'A',
            $score >= 70 => 'B+',
            $score >= 60 => 'B',
            $score >= 50 => 'C',
            default => 'F',
        };
    }

    private function logActivity(?array $actor, string $action, string $entityType, ?int $entityId): void
    {
        $this->execute(
            'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent)
             VALUES (:user_id, :action, :entity_type, :entity_id, :ip_address, :user_agent)',
            [
                'user_id' => $actor['id'] ?? null,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]
        );
    }

    private function scopeCourseRecordsForUser(string $courseColumn, ?array $user): array
    {
        if (!$user || is_admin_role($user['role'] ?? null) || ($user['role'] ?? null) === 'department_head') {
            return ['', []];
        }

        if (($user['role'] ?? '') === 'student') {
            return [
                $courseColumn . ' IN (SELECT course_id FROM course_registrations WHERE user_id = :scope_user_id AND status = "approved")',
                ['scope_user_id' => $user['id']],
            ];
        }

        if (($user['role'] ?? '') === 'lecturer') {
            return [
                $courseColumn . ' IN (SELECT course_id FROM lecturer_courses WHERE lecturer_id = :scope_user_id)',
                ['scope_user_id' => $user['id']],
            ];
        }

        return ['', []];
    }

    private function scopeAssignmentsForUser(?array $user): array
    {
        return $this->scopeCourseRecordsForUser('a.course_id', $user);
    }

    private function scopeResultsForUser(?array $user): array
    {
        if (!$user || is_admin_role($user['role'] ?? null) || ($user['role'] ?? null) === 'department_head') {
            return ['', []];
        }

        if (($user['role'] ?? '') === 'student') {
            return ['r.student_id = :scope_user_id', ['scope_user_id' => $user['id']]];
        }

        if (($user['role'] ?? '') === 'lecturer') {
            return [
                'r.course_id IN (SELECT course_id FROM lecturer_courses WHERE lecturer_id = :scope_user_id)',
                ['scope_user_id' => $user['id']],
            ];
        }

        return ['', []];
    }

    private function scopeRegistrationsForUser(?array $user): array
    {
        if (!$user || is_admin_role($user['role'] ?? null) || ($user['role'] ?? null) === 'department_head') {
            return ['', []];
        }

        if (($user['role'] ?? '') === 'student') {
            return ['cr.user_id = :scope_user_id', ['scope_user_id' => $user['id']]];
        }

        if (($user['role'] ?? '') === 'lecturer') {
            return [
                'cr.course_id IN (SELECT course_id FROM lecturer_courses WHERE lecturer_id = :scope_user_id)',
                ['scope_user_id' => $user['id']],
            ];
        }

        return ['', []];
    }

    private function scopeAnnouncementsForUser(?array $user): array
    {
        if (!$user || is_admin_role($user['role'] ?? null) || ($user['role'] ?? null) === 'department_head') {
            return ['', []];
        }

        if (($user['role'] ?? '') === 'student') {
            return [
                '(a.audience = "global" OR a.course_id IN (SELECT course_id FROM course_registrations WHERE user_id = :scope_user_id AND status = "approved"))',
                ['scope_user_id' => $user['id']],
            ];
        }

        if (($user['role'] ?? '') === 'lecturer') {
            return [
                '(a.audience = "global" OR a.course_id IN (SELECT course_id FROM lecturer_courses WHERE lecturer_id = :scope_user_id))',
                ['scope_user_id' => $user['id']],
            ];
        }

        return ['', []];
    }
}
