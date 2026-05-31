<?php

declare(strict_types=1);

final class DemoRepository
{
    public function __construct(private array $data)
    {
        foreach ($_SESSION['demo_records'] ?? [] as $bucket => $records) {
            $this->data[$bucket] = array_merge($records, $this->data[$bucket] ?? []);
        }

        foreach ($_SESSION['demo_user_status'] ?? [] as $id => $status) {
            foreach ($this->data['users'] as &$user) {
                if ((int) $user['id'] === (int) $id) {
                    $user['status'] = $status;
                }
            }
        }

        if (!empty($_SESSION['demo_deleted_users'])) {
            $deleted = array_map('intval', $_SESSION['demo_deleted_users']);
            $this->data['users'] = array_values(array_filter(
                $this->data['users'],
                static fn (array $user): bool => !in_array((int) $user['id'], $deleted, true)
            ));
        }
    }

    public function findUserByEmail(string $email): ?array
    {
        foreach ($this->data['users'] as $user) {
            if (strcasecmp($user['email'], $email) === 0) {
                return $user;
            }
        }

        return null;
    }

    public function studentNumberExists(string $studentNumber): bool
    {
        if (trim($studentNumber) === '') {
            return false;
        }

        foreach ($this->data['users'] as $user) {
            if (strcasecmp((string) ($user['student_number'] ?? ''), $studentNumber) === 0) {
                return true;
            }
        }

        return false;
    }

    public function getStats(array $user): array
    {
        $studentResults = array_filter($this->data['results'], static fn (array $result): bool => (int) ($result['student_id'] ?? 0) === (int) ($user['id'] ?? 0));
        $averageSource = ($user['role'] ?? '') === 'student' ? $studentResults : $this->data['results'];
        $average = 0;

        if (count($averageSource) > 0) {
            $average = (int) round(array_sum(array_column($averageSource, 'total')) / count($averageSource));
        }

        return [
            'total_students' => count(array_filter($this->data['users'], static fn (array $row): bool => $row['role'] === 'student')),
            'total_lecturers' => count(array_filter($this->data['users'], static fn (array $row): bool => $row['role'] === 'lecturer')),
            'total_courses' => count($this->data['courses']),
            'total_departments' => count($this->data['departments']),
            'total_assignments' => count($this->data['assignments']),
            'total_announcements' => count($this->data['announcements']),
            'pending_registrations' => count(array_filter($this->data['registrations'], static fn (array $row): bool => $row['status'] === 'pending')),
            'materials' => count($this->data['materials']),
            'past_papers' => count($this->data['past_papers']),
            'modules' => count($this->data['modules']),
            'activity_logs' => count($this->data['activity_logs']),
            'average' => $average,
            'registered_courses' => count(array_filter($this->data['registrations'], static fn (array $row): bool => (int) $row['user_id'] === (int) ($user['id'] ?? 0) && $row['status'] === 'approved')),
            'assigned_courses' => count(array_filter($this->data['lecturer_courses'], static fn (array $row): bool => (int) $row['lecturer_id'] === (int) ($user['id'] ?? 0))),
            'enrolled_students' => count(array_unique(array_column($this->data['registrations'], 'user_id'))),
        ];
    }

    public function getUsers(array $filters = []): array
    {
        return $this->filter($this->data['users'], $filters);
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
        return $this->filter($this->data['departments'], $filters);
    }

    public function getCourses(array $filters = []): array
    {
        return $this->filter($this->data['courses'], $filters);
    }

    public function getModules(array $filters = []): array
    {
        return $this->filter($this->data['modules'], $filters);
    }

    public function getAssignments(array $filters = [], ?array $user = null): array
    {
        return $this->filter($this->scopeByUser($this->data['assignments'], $user), $filters);
    }

    public function getMaterials(array $filters = [], ?array $user = null): array
    {
        return $this->filter($this->scopeByUser($this->data['materials'], $user), $filters);
    }

    public function getPastPapers(array $filters = [], ?array $user = null): array
    {
        return $this->filter($this->scopeByUser($this->data['past_papers'], $user), $filters);
    }

    public function getResults(array $filters = [], ?array $user = null): array
    {
        $items = $this->data['results'];
        if (($user['role'] ?? '') === 'student') {
            $items = array_values(array_filter($items, static fn (array $row): bool => (int) $row['student_id'] === (int) $user['id']));
        }

        return $this->filter($items, $filters);
    }

    public function getTimetable(array $filters = [], ?array $user = null): array
    {
        return $this->filter($this->scopeByUser($this->data['timetable'], $user), $filters);
    }

    public function getAnnouncements(array $filters = [], ?array $user = null): array
    {
        return $this->filter($this->data['announcements'], $filters);
    }

    public function getCourseRegistrations(array $filters = [], ?array $user = null): array
    {
        $items = $this->data['registrations'];
        if (($user['role'] ?? '') === 'student') {
            $items = array_values(array_filter($items, static fn (array $row): bool => (int) $row['user_id'] === (int) $user['id']));
        }

        return $this->filter($items, $filters);
    }

    public function getActivityLogs(array $filters = []): array
    {
        return $this->filter($this->data['activity_logs'], $filters);
    }

    public function getSettings(array $filters = []): array
    {
        return $this->filter($this->data['settings'], $filters);
    }

    public function getReports(): array
    {
        return [
            'students_per_course' => [
                ['code' => 'DIT201', 'title' => 'Diploma in Information Technology', 'total' => 2],
                ['code' => 'BCS301', 'title' => 'Bachelor of Computer Science', 'total' => 1],
            ],
            'lecturers_per_department' => [
                ['code' => 'SOC', 'title' => 'School of Computing', 'total' => 2],
                ['code' => 'BUS', 'title' => 'Business Studies', 'total' => 1],
            ],
            'assignments_per_course' => [
                ['code' => 'DIT201', 'title' => 'Diploma in Information Technology', 'total' => 3],
                ['code' => 'BCS301', 'title' => 'Bachelor of Computer Science', 'total' => 1],
            ],
            'registration_totals' => [
                ['title' => 'approved', 'total' => 4],
                ['title' => 'pending', 'total' => 1],
            ],
            'results_summary' => [
                ['title' => 'A', 'total' => 2, 'average_score' => 84.5],
                ['title' => 'B+', 'total' => 1, 'average_score' => 75.0],
            ],
        ];
    }

    public function getRoleOptions(): array
    {
        return $this->data['roles'];
    }

    public function getDepartmentOptions(): array
    {
        return $this->data['departments'];
    }

    public function getCourseOptions(): array
    {
        return $this->data['courses'];
    }

    public function getModuleOptions(): array
    {
        return $this->data['modules'];
    }

    public function getAcademicYearOptions(): array
    {
        return $this->data['academic_years'];
    }

    public function getSemesterOptions(): array
    {
        return $this->data['semesters'];
    }

    public function getLecturerOptions(): array
    {
        return array_values(array_filter($this->data['users'], static fn (array $user): bool => in_array($user['role'], ['lecturer', 'department_head'], true)));
    }

    public function getStudentOptions(): array
    {
        return array_values(array_filter($this->data['users'], static fn (array $user): bool => $user['role'] === 'student'));
    }

    public function createUser(array $data, ?array $actor = null): void
    {
        $record = [
            'id' => $this->nextId('users'),
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'role_name' => role_label($data['role']),
            'status' => $data['status'] ?? 'active',
            'department_id' => $data['department_id'] ?? '',
            'department_name' => $this->nameForId('departments', $data['department_id'] ?? null),
            'program' => $data['program'] ?? '',
            'class_group' => $data['class_group'] ?? '',
            'student_number' => $data['student_number'] ?? '',
            'staff_number' => $data['staff_number'] ?? '',
            'phone' => $data['phone'] ?? '',
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->remember('users', $record, $actor, 'created user ' . $data['email']);
    }

    public function updateUserStatus(int $id, string $status, ?array $actor = null): void
    {
        $_SESSION['demo_user_status'][$id] = $status;
        $this->rememberLog($actor, 'updated user status to ' . $status, 'users');
    }

    public function deleteUser(int $id, ?array $actor = null): void
    {
        $_SESSION['demo_deleted_users'][] = $id;
        $this->rememberLog($actor, 'deleted user', 'users');
    }

    public function createDepartment(array $data, ?array $actor = null): void
    {
        $this->remember('departments', [
            'id' => $this->nextId('departments'),
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'head_name' => $this->nameForId('users', $data['head_user_id'] ?? null),
            'user_count' => 0,
            'course_count' => 0,
        ], $actor, 'created department ' . $data['code']);
    }

    public function createCourse(array $data, ?array $actor = null): void
    {
        $this->remember('courses', [
            'id' => $this->nextId('courses'),
            'code' => strtoupper($data['code']),
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'level' => $data['level'] ?? '',
            'credits' => (int) ($data['credits'] ?? 3),
            'status' => $data['status'] ?? 'active',
            'department_name' => $this->nameForId('departments', $data['department_id'] ?? null),
            'module_count' => 0,
            'student_count' => 0,
        ], $actor, 'created course ' . $data['code']);
    }

    public function createModule(array $data, ?array $actor = null): void
    {
        $course = $this->recordById('courses', $data['course_id'] ?? null);
        $semester = $this->recordById('semesters', $data['semester_id'] ?? null);
        $this->remember('modules', [
            'id' => $this->nextId('modules'),
            'course_id' => (int) $data['course_id'],
            'code' => strtoupper($data['code']),
            'module_name' => $data['title'],
            'title' => $data['title'],
            'course_title' => $course['title'] ?? '',
            'course_code' => $course['code'] ?? '',
            'semester' => $semester['name'] ?? '',
            'lecturer' => 'Unassigned',
            'credits' => (int) ($data['credits'] ?? 3),
            'ca_score' => 0,
            'ue_score' => 0,
            'description' => $data['description'] ?? '',
        ], $actor, 'created module ' . $data['code']);
    }

    public function createRegistration(int $studentId, array $data, ?array $actor = null): void
    {
        $student = $this->recordById('users', $studentId);
        $course = $this->recordById('courses', $data['course_id'] ?? null);
        $year = $this->recordById('academic_years', $data['academic_year_id'] ?? null);
        $semester = $this->recordById('semesters', $data['semester_id'] ?? null);
        $this->remember('registrations', [
            'id' => $this->nextId('registrations'),
            'user_id' => $studentId,
            'student_name' => $student['name'] ?? 'Student',
            'student_email' => $student['email'] ?? '',
            'course_id' => (int) $data['course_id'],
            'course_title' => $course['title'] ?? '',
            'course_code' => $course['code'] ?? '',
            'academic_year' => $year['name'] ?? '',
            'semester' => $semester['name'] ?? '',
            'status' => 'pending',
            'notes' => $data['notes'] ?? '',
            'requested_at' => date('Y-m-d H:i:s'),
        ], $actor, 'requested course registration');
    }

    public function updateRegistrationStatus(int $id, string $status, ?array $actor = null): void
    {
        $this->rememberLog($actor, 'set registration to ' . $status, 'course_registrations');
    }

    public function createAssignment(array $data, ?array $actor = null): void
    {
        $course = $this->recordById('courses', $data['course_id'] ?? null);
        $module = $this->recordById('modules', $data['module_id'] ?? null);
        $lecturer = $this->recordById('users', $data['lecturer_id'] ?? ($actor['id'] ?? null));
        $semester = $this->recordById('semesters', $data['semester_id'] ?? null);
        $this->remember('assignments', [
            'id' => $this->nextId('assignments'),
            'course_id' => (int) $data['course_id'],
            'course_title' => $course['title'] ?? '',
            'course_code' => $course['code'] ?? '',
            'module_name' => $module['module_name'] ?? '',
            'module_code' => $module['code'] ?? '',
            'lecturer' => $lecturer['name'] ?? '',
            'title' => $data['title'],
            'instructions' => $data['instructions'] ?? '',
            'deadline' => $data['deadline'],
            'submission_type' => $data['submission_type'] ?? 'Online upload',
            'status' => $data['status'] ?? 'open',
            'semester' => $semester['name'] ?? '',
            'file_path' => $data['file_path'] ?? '',
        ], $actor, 'created assignment ' . $data['title']);
    }

    public function createAssignmentSubmission(array $data, ?array $actor = null): void
    {
        $this->rememberLog($actor, 'submitted assignment', 'assignment_submissions');
    }

    public function createMaterial(array $data, ?array $actor = null): void
    {
        $course = $this->recordById('courses', $data['course_id'] ?? null);
        $module = $this->recordById('modules', $data['module_id'] ?? null);
        $this->remember('materials', [
            'id' => $this->nextId('materials'),
            'course_id' => (int) $data['course_id'],
            'course_title' => $course['title'] ?? '',
            'course_code' => $course['code'] ?? '',
            'module_name' => $module['module_name'] ?? '',
            'module_code' => $module['code'] ?? '',
            'title' => $data['title'] ?? $data['file_name'],
            'file_name' => $data['file_name'],
            'file_path' => $data['file_path'],
            'file_size' => $data['file_size'],
            'file_type' => $data['file_type'],
            'date_uploaded' => 'Just now',
            'uploaded_by_name' => $actor['name'] ?? 'Demo user',
        ], $actor, 'uploaded material ' . $data['file_name']);
    }

    public function createPastPaper(array $data, ?array $actor = null): void
    {
        $course = $this->recordById('courses', $data['course_id'] ?? null);
        $module = $this->recordById('modules', $data['module_id'] ?? null);
        $year = $this->recordById('academic_years', $data['academic_year_id'] ?? null);
        $semester = $this->recordById('semesters', $data['semester_id'] ?? null);
        $this->remember('past_papers', [
            'id' => $this->nextId('past_papers'),
            'course_id' => (int) $data['course_id'],
            'course_title' => $course['title'] ?? '',
            'course_code' => $course['code'] ?? '',
            'module_name' => $module['module_name'] ?? '',
            'module_code' => $module['code'] ?? '',
            'academic_year' => $year['name'] ?? '',
            'study_year' => $data['study_year'],
            'semester' => $semester['name'] ?? '',
            'exam_type' => $data['exam_type'],
            'title' => $data['title'],
            'file_path' => $data['file_path'],
            'file_name' => $data['file_name'],
        ], $actor, 'uploaded past paper ' . $data['title']);
    }

    public function createTimetable(array $data, ?array $actor = null): void
    {
        $course = $this->recordById('courses', $data['course_id'] ?? null);
        $module = $this->recordById('modules', $data['module_id'] ?? null);
        $lecturer = $this->recordById('users', $data['lecturer_id'] ?? null);
        $this->remember('timetable', [
            'id' => $this->nextId('timetable'),
            'course_id' => (int) $data['course_id'],
            'course_title' => $course['title'] ?? '',
            'course_code' => $course['code'] ?? '',
            'module_name' => $module['module_name'] ?? '',
            'module_code' => $module['code'] ?? '',
            'lecturer' => $lecturer['name'] ?? '',
            'day' => $data['day_of_week'],
            'time' => $data['start_time'] . ' - ' . $data['end_time'],
            'room' => $data['room'],
            'class_group' => $data['class_group'] ?? '',
        ], $actor, 'created timetable slot');
    }

    public function createResult(array $data, ?array $actor = null): void
    {
        $student = $this->recordById('users', $data['student_id'] ?? null);
        $course = $this->recordById('courses', $data['course_id'] ?? null);
        $module = $this->recordById('modules', $data['module_id'] ?? null);
        $semester = $this->recordById('semesters', $data['semester_id'] ?? null);
        $ca = (int) $data['ca_score'];
        $exam = (int) $data['exam_score'];
        $total = min(100, $ca + $exam);
        $this->remember('results', [
            'id' => $this->nextId('results'),
            'student_id' => (int) $data['student_id'],
            'student_name' => $student['name'] ?? '',
            'course_id' => (int) $data['course_id'],
            'course_title' => $course['title'] ?? '',
            'course_code' => $course['code'] ?? '',
            'module_name' => $module['module_name'] ?? '',
            'module_code' => $module['code'] ?? '',
            'semester' => $semester['name'] ?? '',
            'ca_score' => $ca,
            'ue_score' => $exam,
            'total' => $total,
            'grade' => $data['grade'] ?: ($total >= 80 ? 'A' : ($total >= 70 ? 'B+' : ($total >= 60 ? 'B' : ($total >= 50 ? 'C' : 'F')))),
            'status' => $data['status'] ?: ($total >= 50 ? 'pass' : 'repeat'),
        ], $actor, 'uploaded result');
    }

    public function createAnnouncement(array $data, ?array $actor = null): void
    {
        $course = $this->recordById('courses', $data['course_id'] ?? null);
        $this->remember('announcements', [
            'id' => $this->nextId('announcements'),
            'title' => $data['title'],
            'body' => $data['body'],
            'audience' => $data['audience'] ?? 'global',
            'author' => $actor['name'] ?? 'Demo user',
            'course_title' => $course['title'] ?? '',
            'class_group' => $course['code'] ?? 'Global',
            'date_posted' => $data['publish_at'] ?: date('Y-m-d H:i:s'),
        ], $actor, 'posted announcement ' . $data['title']);
    }

    public function updateSetting(string $key, string $value, ?array $actor = null): void
    {
        $this->rememberLog($actor, 'updated setting ' . $key, 'settings');
    }

    private function filter(array $items, array $filters): array
    {
        unset($filters['_where'], $filters['_params']);

        return array_values(array_filter($items, function (array $item) use ($filters): bool {
            foreach ($filters as $key => $value) {
                $value = trim((string) $value);

                if ($value === '') {
                    continue;
                }

                if ($key === 'search') {
                    $haystack = strtolower(implode(' ', array_map('strval', $item)));
                    if (!str_contains($haystack, strtolower($value))) {
                        return false;
                    }

                    continue;
                }

                $candidate = strtolower((string) ($item[$key] ?? ''));
                if ($candidate !== strtolower($value)) {
                    return false;
                }
            }

            return true;
        }));
    }

    private function scopeByUser(array $items, ?array $user): array
    {
        if (!$user || is_admin_role($user['role'] ?? null) || ($user['role'] ?? null) === 'department_head') {
            return $items;
        }

        if (($user['role'] ?? '') === 'student') {
            $approvedCourseIds = array_column(array_filter(
                $this->data['registrations'],
                static fn (array $registration): bool => (int) $registration['user_id'] === (int) $user['id'] && $registration['status'] === 'approved'
            ), 'course_id');

            return array_values(array_filter($items, static fn (array $item): bool => in_array((int) ($item['course_id'] ?? 0), $approvedCourseIds, true)));
        }

        if (($user['role'] ?? '') === 'lecturer') {
            $courseIds = array_column(array_filter(
                $this->data['lecturer_courses'],
                static fn (array $row): bool => (int) $row['lecturer_id'] === (int) $user['id']
            ), 'course_id');

            return array_values(array_filter($items, static fn (array $item): bool => in_array((int) ($item['course_id'] ?? 0), $courseIds, true)));
        }

        return $items;
    }

    private function remember(string $bucket, array $record, ?array $actor, string $action): void
    {
        $_SESSION['demo_records'][$bucket] ??= [];
        array_unshift($_SESSION['demo_records'][$bucket], $record);
        $this->rememberLog($actor, $action, $bucket);
    }

    private function rememberLog(?array $actor, string $action, string $entityType): void
    {
        $_SESSION['demo_records']['activity_logs'] ??= [];
        array_unshift($_SESSION['demo_records']['activity_logs'], [
            'id' => $this->nextId('activity_logs'),
            'user_name' => $actor['name'] ?? 'Demo user',
            'user_email' => $actor['email'] ?? '',
            'action' => $action,
            'entity_type' => $entityType,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function nextId(string $bucket): int
    {
        $ids = array_map('intval', array_column($this->data[$bucket] ?? [], 'id'));

        return ($ids === [] ? 1 : max($ids) + random_int(1, 30));
    }

    private function recordById(string $bucket, mixed $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }

        foreach ($this->data[$bucket] ?? [] as $record) {
            if ((int) ($record['id'] ?? 0) === (int) $id) {
                return $record;
            }
        }

        return null;
    }

    private function nameForId(string $bucket, mixed $id): string
    {
        $record = $this->recordById($bucket, $id);

        return (string) ($record['name'] ?? $record['title'] ?? '');
    }
}
