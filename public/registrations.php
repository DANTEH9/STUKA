<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_login();

$repo = repository();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('registrations.php');
    }

    try {
        $action = post_value('action');
        if ($action === 'request') {
            require_role(['student']);
            $repo->createRegistration((int) $user['id'], [
                'course_id' => post_value('course_id'),
                'academic_year_id' => post_value('academic_year_id'),
                'semester_id' => post_value('semester_id'),
                'notes' => post_value('notes'),
            ], $user);
            set_flash('success', 'Course registration request sent.');
        } elseif (in_array($action, ['approve', 'reject'], true)) {
            require_academic_manager();
            $repo->updateRegistrationStatus((int) post_value('registration_id'), $action === 'approve' ? 'approved' : 'rejected', $user);
            set_flash('success', 'Registration ' . ($action === 'approve' ? 'approved' : 'rejected') . '.');
        }
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
    }

    redirect('registrations.php');
}

$courses = $repo->getCourseOptions();
$years = $repo->getAcademicYearOptions();
$semesters = $repo->getSemesterOptions();
$filters = [
    'status' => request_value('status'),
    'course_id' => request_value('course_id'),
    'search' => request_value('search'),
];
$registrations = $repo->getCourseRegistrations($filters, $user);
$pagination = paginate($registrations, (int) request_value('page', '1'), app_config('items_per_page'));

page_start('Registrations', 'registrations');
?>
<section class="page-title-row">
    <div>
        <h2>Course registrations</h2>
        <p>Students request courses, administrators review approvals, and lecturers see enrolled learners.</p>
    </div>
</section>

<?php if ($user['role'] === 'student'): ?>
<section class="panel compact">
    <div class="panel-heading"><h2>Register for a course</h2></div>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="request">
        <label>
            <span>Course</span>
            <select name="course_id" required>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course['id']) ?>"><?= e($course['code']) ?> - <?= e($course['title']) ?></option>
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
            <select name="semester_id" required>
                <?php foreach ($semesters as $semester): ?>
                    <option value="<?= e($semester['id']) ?>"><?= e($semester['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="wide"><span>Notes</span><textarea name="notes" rows="3"></textarea></label>
        <button class="button primary" type="submit">Request registration</button>
    </form>
</section>
<?php endif; ?>

<section class="page-toolbar">
    <form method="get" class="filter-bar">
        <select name="status" aria-label="Status">
            <option value="">All statuses</option>
            <option value="pending" <?= selected($filters['status'], 'pending') ?>>Pending</option>
            <option value="approved" <?= selected($filters['status'], 'approved') ?>>Approved</option>
            <option value="rejected" <?= selected($filters['status'], 'rejected') ?>>Rejected</option>
        </select>
        <select name="course_id" aria-label="Course">
            <option value="">All courses</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?= e($course['id']) ?>" <?= selected($filters['course_id'], (string) $course['id']) ?>><?= e($course['code']) ?> - <?= e($course['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Search registrations">
        <button class="button yellow" type="submit">Search</button>
    </form>
</section>

<section class="panel table-panel">
    <div class="table-actions"><span><?= e($pagination['total']) ?> registrations</span></div>
    <div class="responsive-table">
        <table>
            <thead><tr><th>Student</th><th>Course</th><th>Period</th><th>Status</th><th>Notes</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($pagination['items'] as $registration): ?>
                    <tr>
                        <td><strong><?= e($registration['student_name']) ?></strong><span><?= e($registration['student_email'] ?? '') ?></span></td>
                        <td><strong><?= e($registration['course_title']) ?></strong><span><?= e($registration['course_code']) ?></span></td>
                        <td><?= e(($registration['academic_year'] ?? '') . ' ' . ($registration['semester'] ?? '')) ?></td>
                        <td><span class="pill <?= $registration['status'] === 'approved' ? 'success' : ($registration['status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= e($registration['status']) ?></span></td>
                        <td><?= e($registration['notes'] ?? '') ?></td>
                        <td class="action-cell">
                            <?php if (can_manage_academics($user['role']) && $registration['status'] === 'pending'): ?>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="registration_id" value="<?= e($registration['id']) ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button class="button primary" type="submit">Approve</button>
                                </form>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="registration_id" value="<?= e($registration['id']) ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button class="button danger" type="submit">Reject</button>
                                </form>
                            <?php else: ?>
                                <span class="muted-text"><?= e($registration['reviewed_by_name'] ?? 'Awaiting review') ?></span>
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
