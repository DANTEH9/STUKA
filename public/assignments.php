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
                'date_given' => post_value('date_given', date('Y-m-d')),
                'deadline' => post_value('deadline'),
                'submission_type' => post_value('submission_type', 'Online / Email'),
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
                'student_comment' => post_value('student_comment'),
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
$submissionModals = [];

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
        <label><span>Date given</span><input type="date" name="date_given" value="<?= e(date('Y-m-d')) ?>" required></label>
        <label><span>Deadline</span><input type="date" name="deadline" required></label>
        <label>
            <span>Submission type</span>
            <select name="submission_type">
                <option>Online / Email</option>
                <option>Hard Copy</option>
                <option>Presentation</option>
                <option>Manual</option>
            </select>
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
                    <th>Date Given</th>
                    <th>Deadline</th>
                    <th>Submission Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagination['items'] as $assignment): ?>
                    <?php
                    $statusMeta = assignment_status_meta($assignment);
                    $canSubmitOnline = $user['role'] === 'student'
                        && ($assignment['status'] ?? '') === 'open'
                        && is_online_submission_type($assignment['submission_type'] ?? '');
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($assignment['title']) ?></strong>
                            <span><?= e($assignment['course_code']) ?> - <?= e($assignment['module_name'] ?: $assignment['course_title']) ?></span>
                            <small><?= e($assignment['lecturer'] ?? 'Unassigned') ?></small>
                        </td>
                        <td><?= e(display_date($assignment['date_given'] ?? '')) ?></td>
                        <td><?= e(display_date($assignment['deadline'])) ?></td>
                        <td><span class="pill soft"><?= e($assignment['submission_type']) ?></span></td>
                        <td>
                            <span class="status-stack">
                                <span class="pill <?= e($statusMeta['class']) ?>"><?= e($statusMeta['label']) ?></span>
                                <?php if (!empty($assignment['submission_id']) && (int) ($assignment['submission_is_late'] ?? 0) === 1): ?>
                                    <small class="table-note"><?= e(late_duration_label((int) $assignment['late_duration_minutes'])) ?></small>
                                <?php endif; ?>
                                <?php if ($user['role'] !== 'student'): ?>
                                    <small class="table-note"><?= e((string) ($assignment['reviewed_count'] ?? 0)) ?> Reviewed / <?= e((string) max(0, (int) ($assignment['submission_count'] ?? 0) - (int) ($assignment['reviewed_count'] ?? 0))) ?> Not Reviewed</small>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="action-cell">
                            <?php if (!empty($assignment['file_path'])): ?>
                                <a class="button quiet" href="<?= e($assignment['file_path']) ?>" download>Download</a>
                            <?php endif; ?>
                            <?php if ($canSubmitOnline): ?>
                                <?php $modalId = 'submit-assignment-' . (int) $assignment['id']; $submissionModals[$modalId] = $assignment; ?>
                                <button class="button primary" type="button" data-modal-open="<?= e($modalId) ?>">
                                    <?= !empty($assignment['submission_id']) ? 'Resubmit Assignment' : 'Submit Assignment' ?>
                                </button>
                            <?php elseif ($user['role'] === 'student'): ?>
                                <span class="muted-text"><?= e(is_online_submission_type($assignment['submission_type'] ?? '') ? 'Unavailable' : 'No upload required') ?></span>
                            <?php elseif (can_upload_teaching_files($user['role'])): ?>
                                <a class="button primary" href="submissions.php?assignment_id=<?= e($assignment['id']) ?>">View Submissions</a>
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

<?php foreach ($submissionModals as $modalId => $assignment): ?>
    <div class="modal-backdrop" id="<?= e($modalId) ?>" data-modal hidden>
        <section class="modal-card">
            <div class="modal-header">
                <div>
                    <h2><?= e($assignment['title']) ?></h2>
                    <span class="table-note"><?= e($assignment['course_code']) ?> - <?= e($assignment['module_name'] ?: $assignment['course_title']) ?> - Due <?= e(display_date($assignment['deadline'])) ?></span>
                </div>
                <button class="button quiet" type="button" data-modal-close>Close</button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="submit">
                    <input type="hidden" name="assignment_id" value="<?= e($assignment['id']) ?>">
                    <label class="drop-zone" data-drop-zone>
                        <strong>Drop assignment file here</strong>
                        <span data-file-name>PDF, DOCX, PPT/PPTX, ZIP, or image</span>
                        <input type="file" name="submission_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.jpg,.jpeg,.png" required>
                    </label>
                    <label class="stacked-form modal-note">
                        <span>Comment</span>
                        <textarea name="student_comment" rows="3" placeholder="Optional note for the lecturer"><?= e($assignment['student_comment'] ?? '') ?></textarea>
                    </label>
                </div>
                <div class="modal-footer">
                    <span class="table-note"><?= !empty($assignment['submission_id']) ? 'This will replace your previous upload.' : 'Your lecturer will be notified after upload.' ?></span>
                    <button class="button primary" type="submit">Submit Assignment</button>
                </div>
            </form>
        </section>
    </div>
<?php endforeach; ?>
<?php
pagination_controls($pagination);
page_end();
