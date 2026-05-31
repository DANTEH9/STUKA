<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_login();

$repo = repository();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role(['super_admin', 'admin', 'lecturer']);
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('results.php');
    }

    try {
        $repo->createResult([
            'student_id' => post_value('student_id'),
            'course_id' => post_value('course_id'),
            'module_id' => post_value('module_id'),
            'academic_year_id' => post_value('academic_year_id'),
            'semester_id' => post_value('semester_id'),
            'ca_score' => post_value('ca_score', '0'),
            'exam_score' => post_value('exam_score', '0'),
            'grade' => post_value('grade'),
            'status' => post_value('status'),
        ], $user);
        set_flash('success', 'Result uploaded.');
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
    }

    redirect('results.php');
}

$students = $repo->getStudentOptions();
$courses = $repo->getCourseOptions();
$modules = $repo->getModuleOptions();
$years = $repo->getAcademicYearOptions();
$semesters = $repo->getSemesterOptions();
$filters = [
    'course_id' => request_value('course_id'),
    'semester' => request_value('semester'),
    'status' => request_value('status'),
    'search' => request_value('search'),
];
$results = $repo->getResults($filters, $user);
$pagination = paginate($results, (int) request_value('page', '1'), app_config('items_per_page'));

page_start('Results', 'results');
?>
<section class="page-title-row">
    <div>
        <h2>Results</h2>
        <p>Lecturers and admins upload marks; students only see their own released results.</p>
    </div>
</section>

<?php if (can_manage_results($user['role'])): ?>
<section class="panel compact">
    <div class="panel-heading"><h2>Upload result</h2></div>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>
            <span>Student</span>
            <select name="student_id" required>
                <?php foreach ($students as $student): ?>
                    <option value="<?= e($student['id']) ?>"><?= e($student['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Course</span>
            <select name="course_id" required>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course['id']) ?>"><?= e($course['code']) ?> - <?= e($course['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Module</span>
            <select name="module_id">
                <option value="">Whole course</option>
                <?php foreach ($modules as $module): ?>
                    <option value="<?= e($module['id']) ?>"><?= e($module['code']) ?> - <?= e($module['title'] ?? $module['module_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Academic year</span>
            <select name="academic_year_id">
                <?php foreach ($years as $year): ?>
                    <option value="<?= e($year['id']) ?>"><?= e($year['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Semester</span>
            <select name="semester_id">
                <?php foreach ($semesters as $semester): ?>
                    <option value="<?= e($semester['id']) ?>"><?= e($semester['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><span>CA score</span><input type="number" min="0" max="60" name="ca_score" required></label>
        <label><span>Exam score</span><input type="number" min="0" max="40" name="exam_score" required></label>
        <label><span>Grade</span><input name="grade" placeholder="Auto if blank"></label>
        <label>
            <span>Status</span>
            <select name="status"><option value="">Auto</option><option value="pass">Pass</option><option value="repeat">Repeat</option></select>
        </label>
        <button class="button primary" type="submit">Upload result</button>
    </form>
</section>
<?php endif; ?>

<section class="page-toolbar">
    <form method="get" class="filter-bar">
        <select name="course_id" aria-label="Course">
            <option value="">All courses</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?= e($course['id']) ?>" <?= selected($filters['course_id'], (string) $course['id']) ?>><?= e($course['code']) ?> - <?= e($course['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="semester" aria-label="Semester">
            <option value="">All semesters</option>
            <?php foreach (array_unique(array_column($semesters, 'name')) as $semesterName): ?>
                <option value="<?= e($semesterName) ?>" <?= selected($filters['semester'], $semesterName) ?>><?= e($semesterName) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" aria-label="Status">
            <option value="">All statuses</option>
            <option value="pass" <?= selected($filters['status'], 'pass') ?>>Pass</option>
            <option value="repeat" <?= selected($filters['status'], 'repeat') ?>>Repeat</option>
        </select>
        <input type="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Search results">
        <button class="button yellow" type="submit">Search</button>
    </form>
</section>

<section class="panel table-panel">
    <div class="table-actions"><span><?= e($pagination['total']) ?> result records</span></div>
    <div class="responsive-table">
        <table>
            <thead><tr><th>Student</th><th>Course</th><th>Module</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($pagination['items'] as $result): ?>
                    <tr>
                        <td><?= e($result['student_name'] ?? $user['name']) ?></td>
                        <td><strong><?= e($result['course_title']) ?></strong><span><?= e($result['course_code']) ?></span></td>
                        <td><?= e($result['module_name'] ?: 'Whole course') ?></td>
                        <td><?= e($result['ca_score']) ?></td>
                        <td><?= e($result['ue_score']) ?></td>
                        <td><strong><?= e($result['total']) ?></strong></td>
                        <td><span class="pill success"><?= e($result['grade']) ?></span></td>
                        <td><?= e($result['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
pagination_controls($pagination);
page_end();
