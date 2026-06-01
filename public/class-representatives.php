<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_academic_manager();

$repo = repository();
$user = current_user();
$departments = $repo->getDepartmentOptions();
$courses = $repo->getCourseOptions();
$academicYears = $repo->getAcademicYearOptions();
$semesters = $repo->getSemesterOptions();
$studyYears = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

$defaultScope = $repo->getDefaultClassRepresentativeScope();
$filters = [
    'department_id' => request_value('department_id', (string) ($defaultScope['department_id'] ?? ($departments[0]['id'] ?? ''))),
    'course_id' => request_value('course_id', (string) ($defaultScope['course_id'] ?? ($courses[0]['id'] ?? ''))),
    'study_year' => request_value('study_year', (string) ($defaultScope['study_year'] ?? '2nd Year')),
    'semester_id' => request_value('semester_id', (string) ($defaultScope['semester_id'] ?? ($semesters[0]['id'] ?? ''))),
    'academic_year_id' => request_value('academic_year_id', (string) ($defaultScope['academic_year_id'] ?? ($academicYears[0]['id'] ?? ''))),
    'search' => request_value('search'),
];
$returnQuery = http_build_query(array_filter($filters, static fn ($value) => $value !== '' && $value !== null));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('class-representatives.php' . ($returnQuery !== '' ? '?' . $returnQuery : ''));
    }

    $postReturn = trim((string) ($_POST['return_to'] ?? ''));
    $target = 'class-representatives.php' . ($postReturn !== '' ? '?' . $postReturn : '');

    try {
        $action = post_value('action');
        if ($action === 'assign') {
            $repo->assignClassRepresentative([
                'department_id' => post_value('department_id'),
                'course_id' => post_value('course_id'),
                'academic_year_id' => post_value('academic_year_id'),
                'semester_id' => post_value('semester_id'),
                'study_year' => post_value('study_year'),
                'student_id' => post_value('student_id'),
            ], $user);
            set_flash('success', 'Class representative assigned.');
        } elseif ($action === 'unassign') {
            $repo->unassignClassRepresentative((int) post_value('representative_id'), $user);
            set_flash('success', 'Class representative unassigned.');
        }
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
    }

    redirect($target);
}

$currentRepresentative = $repo->getCurrentClassRepresentative($filters);
$students = $repo->getClassRepresentativeStudents($filters);
$pagination = paginate($students, (int) request_value('page', '1'), app_config('items_per_page'));

page_start('Class Representatives', 'class-representatives');
?>
<section class="page-title-row cr-title-row">
    <div>
        <span class="eyebrow">Academic management</span>
        <h2>Class representative management</h2>
        <p>Assign one active representative for the selected class, course, semester, and academic year.</p>
    </div>
    <div class="hero-actions">
        <a class="button quiet" href="students.php">Students</a>
        <a class="button primary" href="#student-roster">Load Students</a>
    </div>
</section>

<section class="page-toolbar cr-filter-panel">
    <form method="get" class="cr-filter-grid">
        <label>
            <span>Department</span>
            <select name="department_id" aria-label="Department">
                <option value="">All departments</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= e($department['id']) ?>" <?= selected($filters['department_id'], (string) $department['id']) ?>><?= e($department['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Course</span>
            <select name="course_id" aria-label="Course" required>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course['id']) ?>" <?= selected($filters['course_id'], (string) $course['id']) ?>><?= e($course['code']) ?> - <?= e($course['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Year</span>
            <select name="study_year" aria-label="Study year">
                <?php foreach ($studyYears as $year): ?>
                    <option value="<?= e($year) ?>" <?= selected($filters['study_year'], $year) ?>><?= e($year) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Semester</span>
            <select name="semester_id" aria-label="Semester" required>
                <?php foreach ($semesters as $semester): ?>
                    <option value="<?= e($semester['id']) ?>" <?= selected($filters['semester_id'], (string) $semester['id']) ?>><?= e($semester['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Academic Year</span>
            <select name="academic_year_id" aria-label="Academic year" required>
                <?php foreach ($academicYears as $academicYear): ?>
                    <option value="<?= e($academicYear['id']) ?>" <?= selected($filters['academic_year_id'], (string) $academicYear['id']) ?>><?= e($academicYear['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="cr-search-field">
            <span>Student search</span>
            <input type="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Search name, registration number, email, or phone">
        </label>
        <button class="button yellow" type="submit">Load Students</button>
    </form>
</section>

<section class="cr-layout">
    <article class="cr-profile-card">
        <div class="panel-heading">
            <div>
                <h2>Current Class Representative</h2>
                <span class="table-note"><?= e($filters['study_year']) ?> / <?= e($currentRepresentative['semester'] ?? 'Selected semester') ?> / <?= e($currentRepresentative['academic_year'] ?? 'Selected academic year') ?></span>
            </div>
        </div>
        <?php if ($currentRepresentative): ?>
            <div class="cr-profile-main">
                <div class="avatar-initials huge"><?= e(initials($currentRepresentative['name'])) ?></div>
                <div>
                    <span class="pill accent">Active CR</span>
                    <h3><?= e($currentRepresentative['name']) ?></h3>
                    <dl class="cr-profile-details">
                        <div><dt>Registration number</dt><dd><?= e($currentRepresentative['student_number'] ?: 'Not recorded') ?></dd></div>
                        <div><dt>Email</dt><dd><?= e($currentRepresentative['email']) ?></dd></div>
                        <div><dt>Phone</dt><dd><?= e($currentRepresentative['phone'] ?: 'Not recorded') ?></dd></div>
                        <div><dt>Date assigned</dt><dd><?= e(display_datetime($currentRepresentative['assigned_at'])) ?></dd></div>
                    </dl>
                </div>
            </div>
            <div class="button-row">
                <a class="button primary" href="#student-roster">Replace CR</a>
                <form method="post" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="unassign">
                    <input type="hidden" name="representative_id" value="<?= e($currentRepresentative['id']) ?>">
                    <input type="hidden" name="return_to" value="<?= e($returnQuery) ?>">
                    <button class="button danger" type="submit">Unassign CR</button>
                </form>
            </div>
        <?php else: ?>
            <div class="cr-empty">
                <strong>No active CR</strong>
                <p>Select a student from the roster to assign a representative for this class.</p>
                <a class="button primary" href="#student-roster">Choose student</a>
            </div>
        <?php endif; ?>
    </article>

    <article class="cr-guidance-panel">
        <span class="eyebrow">Selection scope</span>
        <h2><?= e($currentRepresentative['course_code'] ?? 'Class roster') ?></h2>
        <p><?= e($currentRepresentative['course_title'] ?? 'Students shown below are pulled from approved registrations for the selected filters.') ?></p>
        <div class="cr-scope-list">
            <span><?= e((string) $pagination['total']) ?> students loaded</span>
            <span><?= e($currentRepresentative ? 'Representative active' : 'Awaiting assignment') ?></span>
        </div>
    </article>
</section>

<section class="panel table-panel cr-table-panel" id="student-roster">
    <div class="table-actions">
        <span><?= e((string) $pagination['total']) ?> eligible students</span>
        <span class="table-note">Use Assign as CR to replace the active representative for this selected scope.</span>
    </div>
    <div class="responsive-table">
        <table class="cr-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Program</th>
                    <th>Contact</th>
                    <th>Class</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagination['items'] as $student): ?>
                    <?php $profileModalId = 'student-profile-' . (int) $student['id']; ?>
                    <tr>
                        <td>
                            <strong><?= e($student['name']) ?></strong>
                            <span><?= e($student['student_number'] ?: $student['email']) ?></span>
                        </td>
                        <td><?= e($student['program'] ?? '') ?></td>
                        <td><span><?= e($student['email']) ?></span><small><?= e($student['phone'] ?: 'No phone recorded') ?></small></td>
                        <td><span class="pill soft"><?= e($student['class_group'] ?? '') ?></span></td>
                        <td>
                            <?php if (!empty($student['active_cr_id'])): ?>
                                <span class="pill accent">Active CR</span>
                            <?php else: ?>
                                <span class="pill <?= ($student['status'] ?? '') === 'active' ? 'success' : 'warning' ?>"><?= e($student['status'] ?? '') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="action-cell">
                            <button class="button quiet" type="button" data-modal-open="<?= e($profileModalId) ?>">View Profile</button>
                            <?php if (!empty($student['active_cr_id'])): ?>
                                <button class="button subtle" type="button" disabled>Assign as CR</button>
                            <?php else: ?>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="assign">
                                    <input type="hidden" name="student_id" value="<?= e($student['id']) ?>">
                                    <input type="hidden" name="department_id" value="<?= e($filters['department_id']) ?>">
                                    <input type="hidden" name="course_id" value="<?= e($filters['course_id']) ?>">
                                    <input type="hidden" name="study_year" value="<?= e($filters['study_year']) ?>">
                                    <input type="hidden" name="semester_id" value="<?= e($filters['semester_id']) ?>">
                                    <input type="hidden" name="academic_year_id" value="<?= e($filters['academic_year_id']) ?>">
                                    <input type="hidden" name="return_to" value="<?= e($returnQuery) ?>">
                                    <button class="button primary" type="submit">Assign as CR</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($pagination['items'] === []): ?>
                    <tr>
                        <td colspan="6"><span class="muted-text">No approved students match the selected class filters.</span></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php foreach ($pagination['items'] as $student): ?>
    <?php $profileModalId = 'student-profile-' . (int) $student['id']; ?>
    <div class="modal-backdrop" id="<?= e($profileModalId) ?>" data-modal hidden>
        <section class="modal-card">
            <div class="modal-header">
                <div>
                    <h2><?= e($student['name']) ?></h2>
                    <span class="table-note"><?= e($student['student_number'] ?: $student['email']) ?></span>
                </div>
                <button class="button quiet" type="button" data-modal-close>Close</button>
            </div>
            <div class="modal-body">
                <dl class="details-list">
                    <div><dt>Program</dt><dd><?= e($student['program'] ?? '') ?></dd></div>
                    <div><dt>Department</dt><dd><?= e($student['department_name'] ?? 'Not assigned') ?></dd></div>
                    <div><dt>Course</dt><dd><?= e(($student['course_code'] ?? '') . ' - ' . ($student['course_title'] ?? '')) ?></dd></div>
                    <div><dt>Class</dt><dd><?= e($student['class_group'] ?? '') ?></dd></div>
                    <div><dt>Email</dt><dd><?= e($student['email']) ?></dd></div>
                    <div><dt>Phone</dt><dd><?= e($student['phone'] ?: 'Not recorded') ?></dd></div>
                </dl>
            </div>
        </section>
    </div>
<?php endforeach; ?>

<?php
pagination_controls($pagination);
page_end();
