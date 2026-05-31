<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_login();

$repo = repository();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_academic_manager();
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('courses.php');
    }

    try {
        $repo->createCourse([
            'department_id' => post_value('department_id'),
            'code' => post_value('code'),
            'title' => post_value('title'),
            'description' => post_value('description'),
            'level' => post_value('level'),
            'credits' => post_value('credits', '3'),
            'status' => post_value('status', 'active'),
            'lecturer_id' => post_value('lecturer_id'),
            'academic_year_id' => post_value('academic_year_id'),
            'semester_id' => post_value('semester_id'),
        ], $user);
        set_flash('success', 'Course created.');
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
    }

    redirect('courses.php');
}

$filters = [
    'department_id' => request_value('department_id'),
    'status' => request_value('status'),
    'search' => request_value('search'),
];
$courses = $repo->getCourses($filters);
$pagination = paginate($courses, (int) request_value('page', '1'), app_config('items_per_page'));
$departments = $repo->getDepartmentOptions();
$lecturers = $repo->getLecturerOptions();
$years = $repo->getAcademicYearOptions();
$semesters = $repo->getSemesterOptions();

page_start('Courses', 'courses');
?>
<section class="page-title-row">
    <div>
        <h2>Courses</h2>
        <p>Browse and manage course programmes, registrations, modules, and lecturer ownership.</p>
    </div>
</section>

<?php if (can_manage_academics($user['role'])): ?>
<section class="panel compact">
    <div class="panel-heading"><h2>Add course</h2></div>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label><span>Code</span><input name="code" required maxlength="30"></label>
        <label><span>Title</span><input name="title" required></label>
        <label>
            <span>Department</span>
            <select name="department_id" required>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= e($department['id']) ?>"><?= e($department['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><span>Level</span><input name="level" placeholder="Diploma, Degree"></label>
        <label><span>Credits</span><input type="number" min="1" max="480" name="credits" value="120"></label>
        <label>
            <span>Status</span>
            <select name="status"><option value="active">Active</option><option value="archived">Archived</option></select>
        </label>
        <label>
            <span>Lead lecturer</span>
            <select name="lecturer_id">
                <option value="">Assign later</option>
                <?php foreach ($lecturers as $lecturer): ?>
                    <option value="<?= e($lecturer['id']) ?>"><?= e($lecturer['name']) ?></option>
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
        <label class="wide"><span>Description</span><textarea name="description" rows="3"></textarea></label>
        <button class="button primary" type="submit">Create course</button>
    </form>
</section>
<?php endif; ?>

<section class="page-toolbar">
    <form method="get" class="filter-bar">
        <select name="department_id" aria-label="Department">
            <option value="">All departments</option>
            <?php foreach ($departments as $department): ?>
                <option value="<?= e($department['id']) ?>" <?= selected($filters['department_id'], (string) $department['id']) ?>><?= e($department['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" aria-label="Status">
            <option value="">All statuses</option>
            <option value="active" <?= selected($filters['status'], 'active') ?>>Active</option>
            <option value="archived" <?= selected($filters['status'], 'archived') ?>>Archived</option>
        </select>
        <input type="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Search courses">
        <button class="button yellow" type="submit">Search</button>
    </form>
</section>

<section class="course-grid">
    <?php foreach ($pagination['items'] as $course): ?>
        <article class="course-card">
            <div>
                <span class="eyebrow"><?= e($course['code']) ?> - <?= e($course['level'] ?? '') ?></span>
                <h2><?= e($course['title']) ?></h2>
                <p><?= e($course['description'] ?? '') ?></p>
            </div>
            <dl class="metric-row">
                <div><dt>Modules</dt><dd><?= e($course['module_count'] ?? 0) ?></dd></div>
                <div><dt>Students</dt><dd><?= e($course['student_count'] ?? 0) ?></dd></div>
                <div><dt>Credits</dt><dd><?= e($course['credits'] ?? 0) ?></dd></div>
            </dl>
            <div class="card-actions">
                <span class="pill <?= ($course['status'] ?? '') === 'active' ? 'success' : 'warning' ?>"><?= e($course['status']) ?></span>
                <a class="button subtle" href="modules.php?course_id=<?= e($course['id']) ?>">Modules</a>
                <a class="button quiet" href="registrations.php?course_id=<?= e($course['id']) ?>">Registrations</a>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<?php
if ($pagination['items'] === []) {
    empty_state('No courses found', 'Try a different filter or add a course when you have permission.');
}
pagination_controls($pagination);
page_end();
