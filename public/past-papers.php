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
        redirect('past-papers.php');
    }

    try {
        $upload = safe_uploaded_file('paper_file', 'past-papers');
        $repo->createPastPaper([
            'course_id' => post_value('course_id'),
            'module_id' => post_value('module_id'),
            'academic_year_id' => post_value('academic_year_id'),
            'semester_id' => post_value('semester_id'),
            'study_year' => post_value('study_year'),
            'exam_type' => post_value('exam_type'),
            'title' => post_value('title') ?: $upload['original_name'],
            'file_path' => $upload['file_path'],
            'file_name' => $upload['original_name'],
        ], $user);
        set_flash('success', 'Past paper uploaded.');
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
    }

    redirect('past-papers.php');
}

$courses = $repo->getCourseOptions();
$modules = $repo->getModuleOptions();
$years = $repo->getAcademicYearOptions();
$semesters = $repo->getSemesterOptions();
$filters = [
    'academic_year' => request_value('academic_year'),
    'study_year' => request_value('study_year'),
    'semester' => request_value('semester'),
    'exam_type' => request_value('exam_type'),
    'course_id' => request_value('course_id'),
    'search' => request_value('search'),
];
$papers = $repo->getPastPapers($filters, $user);
$pagination = paginate($papers, (int) request_value('page', '1'), app_config('items_per_page'));

page_start('Past Papers', 'past-papers');
?>
<section class="page-title-row">
    <div>
        <h2>Past papers</h2>
        <p>Store revision papers by course, module, semester, year, and exam type.</p>
    </div>
</section>

<?php if (can_upload_teaching_files($user['role'])): ?>
<section class="panel compact">
    <div class="panel-heading"><h2>Upload past paper</h2></div>
    <form method="post" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label><span>Title</span><input name="title" required></label>
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
            <select name="academic_year_id" required>
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
        <label><span>Study year</span><input name="study_year" placeholder="2nd Year" required></label>
        <label><span>Exam type</span><input name="exam_type" placeholder="UE, Test 1, Supplementary" required></label>
        <label class="file-input"><span>File</span><input type="file" name="paper_file" required></label>
        <button class="button primary" type="submit">Upload paper</button>
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
        <select name="academic_year" aria-label="Academic year">
            <option value="">All years</option>
            <?php foreach ($years as $year): ?>
                <option value="<?= e($year['name']) ?>" <?= selected($filters['academic_year'], $year['name']) ?>><?= e($year['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="semester" aria-label="Semester">
            <option value="">All semesters</option>
            <?php foreach (array_unique(array_column($semesters, 'name')) as $semesterName): ?>
                <option value="<?= e($semesterName) ?>" <?= selected($filters['semester'], $semesterName) ?>><?= e($semesterName) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Search papers">
        <button class="button yellow" type="submit">Search</button>
    </form>
</section>

<section class="panel table-panel">
    <div class="table-actions"><span><?= e($pagination['total']) ?> past papers</span></div>
    <div class="responsive-table">
        <table>
            <thead><tr><th>Paper</th><th>Course</th><th>Module</th><th>Academic year</th><th>Semester</th><th>Type</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($pagination['items'] as $paper): ?>
                    <tr>
                        <td><strong><?= e($paper['title'] ?? $paper['exam_type']) ?></strong><span><?= e($paper['study_year']) ?></span></td>
                        <td><strong><?= e($paper['course_title']) ?></strong><span><?= e($paper['course_code']) ?></span></td>
                        <td><?= e($paper['module_name'] ?: 'Whole course') ?></td>
                        <td><?= e($paper['academic_year']) ?></td>
                        <td><?= e($paper['semester']) ?></td>
                        <td><?= e($paper['exam_type']) ?></td>
                        <td><a class="button yellow" href="<?= e($paper['file_path'] ?? '#') ?>" download>Download</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
pagination_controls($pagination);
page_end();
