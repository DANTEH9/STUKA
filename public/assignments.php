<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_login();

$repo = repository();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('assignments.php');
    }

    try {
        $action = post_value('action');
        if ($action === 'create') {
            require_role(['super_admin', 'admin', 'lecturer']);
            $filePath = null;
            if (!empty($_FILES['assignment_file']['name']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
                $upload = safe_uploaded_file('assignment_file', 'assignments');
                $filePath = $upload['file_path'];
            }
            $repo->createAssignment([
                'course_id' => post_value('course_id'),
                'module_id' => post_value('module_id'),
                'lecturer_id' => post_value('lecturer_id') ?: ($user['role'] === 'lecturer' ? $user['id'] : ''),
                'semester_id' => post_value('semester_id'),
                'title' => post_value('title'),
                'instructions' => post_value('instructions'),
                'deadline' => post_value('deadline'),
                'submission_type' => post_value('submission_type', 'Online upload'),
                'status' => post_value('status', 'open'),
                'file_path' => $filePath,
            ], $user);
            set_flash('success', 'Assignment created.');
        } elseif ($action === 'submit') {
            require_role(['student']);
            $upload = safe_uploaded_file('submission_file', 'submissions');
            $repo->createAssignmentSubmission([
                'assignment_id' => post_value('assignment_id'),
                'student_id' => $user['id'],
                'file_path' => $upload['file_path'],
                'original_name' => $upload['original_name'],
            ], $user);
            set_flash('success', 'Assignment submitted.');
        }
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
    }

    redirect('assignments.php');
}

$courses = $repo->getCourseOptions();
$modules = $repo->getModuleOptions();
$semesters = $repo->getSemesterOptions();
$lecturers = $repo->getLecturerOptions();
$filters = [
    'course_id' => request_value('course_id'),
    'semester' => request_value('semester'),
    'submission_type' => request_value('submission_type'),
    'status' => request_value('status'),
    'search' => request_value('search'),
];
$assignments = $repo->getAssignments($filters, $user);
$pagination = paginate($assignments, (int) request_value('page', '1'), app_config('items_per_page'));

page_start('Assignments', 'assignments');
?>
<section class="page-title-row">
    <div>
        <h2>Assignments</h2>
        <p>Create deadlines, attach instructions, and let students submit work securely.</p>
    </div>
</section>

<?php if (can_upload_teaching_files($user['role'])): ?>
<section class="panel compact">
    <div class="panel-heading"><h2>Create assignment</h2></div>
    <form method="post" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create">
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
            <span>Lecturer</span>
            <select name="lecturer_id">
                <option value="">Use current user</option>
                <?php foreach ($lecturers as $lecturer): ?>
                    <option value="<?= e($lecturer['id']) ?>"><?= e($lecturer['name']) ?></option>
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
        <label><span>Deadline</span><input type="date" name="deadline" required></label>
        <label>
            <span>Submission type</span>
            <select name="submission_type"><option>Online upload</option><option>Hard copy</option><option>Email</option></select>
        </label>
        <label>
            <span>Status</span>
            <select name="status"><option value="open">Open</option><option value="closed">Closed</option></select>
        </label>
        <label class="file-input"><span>Attachment</span><input type="file" name="assignment_file"></label>
        <label class="wide"><span>Instructions</span><textarea name="instructions" rows="3"></textarea></label>
        <button class="button primary" type="submit">Create assignment</button>
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
            <option value="open" <?= selected($filters['status'], 'open') ?>>Open</option>
            <option value="closed" <?= selected($filters['status'], 'closed') ?>>Closed</option>
        </select>
        <input type="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Search assignments">
        <button class="button yellow" type="submit">Search</button>
    </form>
</section>

<section class="panel table-panel">
    <div class="table-actions">
        <span><?= e($pagination['total']) ?> assignments</span>
    </div>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>Assignment</th>
                    <th>Course</th>
                    <th>Lecturer</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagination['items'] as $assignment): ?>
                    <tr>
                        <td>
                            <strong><?= e($assignment['title']) ?></strong>
                            <span><?= e($assignment['module_name'] ?: $assignment['course_title']) ?></span>
                        </td>
                        <td><strong><?= e($assignment['course_title']) ?></strong><span><?= e($assignment['course_code']) ?></span></td>
                        <td><?= e($assignment['lecturer'] ?? 'Unassigned') ?></td>
                        <td><?= e(display_date($assignment['deadline'])) ?></td>
                        <td><span class="pill <?= ($assignment['status'] ?? '') === 'open' ? 'success' : 'warning' ?>"><?= e($assignment['status']) ?></span></td>
                        <td class="action-cell">
                            <?php if (!empty($assignment['file_path'])): ?>
                                <a class="button quiet" href="<?= e($assignment['file_path']) ?>" download>Download</a>
                            <?php endif; ?>
                            <?php if ($user['role'] === 'student' && ($assignment['status'] ?? '') === 'open'): ?>
                                <form method="post" enctype="multipart/form-data" class="inline-upload">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="submit">
                                    <input type="hidden" name="assignment_id" value="<?= e($assignment['id']) ?>">
                                    <input type="file" name="submission_file" required>
                                    <button class="button primary" type="submit">Submit</button>
                                </form>
                            <?php else: ?>
                                <a class="button subtle" href="assignments.php?search=<?= e(urlencode($assignment['title'])) ?>">View</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
pagination_controls($pagination);
page_end();
