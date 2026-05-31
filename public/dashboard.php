<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_login();

$user = current_user();
$repo = repository();
$stats = $repo->getStats($user);
$role = $user['role'];
$announcements = array_slice($repo->getAnnouncements([], $user), 0, 3);
$assignments = array_slice($repo->getAssignments([], $user), 0, 4);
$materials = array_slice($repo->getMaterials([], $user), 0, 4);
$registrations = array_slice($repo->getCourseRegistrations(['status' => 'pending'], $user), 0, 4);

$cardsByRole = [
    'super_admin' => [
        ['label' => 'Students', 'value' => $stats['total_students'], 'meta' => 'Active student records'],
        ['label' => 'Lecturers', 'value' => $stats['total_lecturers'], 'meta' => 'Teaching accounts'],
        ['label' => 'Courses', 'value' => $stats['total_courses'], 'meta' => 'Configured courses'],
        ['label' => 'Departments', 'value' => $stats['total_departments'], 'meta' => 'Academic units'],
        ['label' => 'Assignments', 'value' => $stats['total_assignments'], 'meta' => 'Course work items'],
        ['label' => 'Activity logs', 'value' => $stats['activity_logs'], 'meta' => 'Recent system events'],
    ],
    'admin' => [
        ['label' => 'Students', 'value' => $stats['total_students'], 'meta' => 'Managed learners'],
        ['label' => 'Courses', 'value' => $stats['total_courses'], 'meta' => 'Registration catalogue'],
        ['label' => 'Pending registrations', 'value' => $stats['pending_registrations'], 'meta' => 'Need review'],
        ['label' => 'Announcements', 'value' => $stats['total_announcements'], 'meta' => 'Published notices'],
    ],
    'department_head' => [
        ['label' => 'Department students', 'value' => $stats['total_students'], 'meta' => 'Visible learners'],
        ['label' => 'Lecturers', 'value' => $stats['total_lecturers'], 'meta' => 'Teaching staff'],
        ['label' => 'Courses', 'value' => $stats['total_courses'], 'meta' => 'Department coverage'],
        ['label' => 'Average score', 'value' => $stats['average'] . '%', 'meta' => 'Current results mean'],
    ],
    'lecturer' => [
        ['label' => 'Assigned courses', 'value' => $stats['assigned_courses'] ?? 0, 'meta' => 'Teaching load'],
        ['label' => 'Enrolled students', 'value' => $stats['enrolled_students'] ?? 0, 'meta' => 'Approved registrations'],
        ['label' => 'Assignments', 'value' => $stats['total_assignments'], 'meta' => 'Published work'],
        ['label' => 'Materials', 'value' => $stats['materials'], 'meta' => 'Uploaded resources'],
    ],
    'student' => [
        ['label' => 'Registered courses', 'value' => $stats['registered_courses'] ?? 0, 'meta' => 'Approved courses'],
        ['label' => 'Assignments', 'value' => $stats['total_assignments'], 'meta' => 'Available tasks'],
        ['label' => 'Materials', 'value' => $stats['materials'], 'meta' => 'Study files'],
        ['label' => 'Average score', 'value' => $stats['average'] . '%', 'meta' => 'Released results'],
    ],
];

$cards = $cardsByRole[$role] ?? $cardsByRole['student'];

page_start('Dashboard', 'dashboard');
?>
<section class="page-title-row dashboard-hero">
    <div>
        <span class="eyebrow"><?= e(role_label($role)) ?></span>
        <h2>Welcome back, <?= e(explode(' ', $user['name'])[0]) ?></h2>
        <p><?= e($user['program'] ?? 'Track courses, teaching work, registrations, resources, and academic progress.') ?></p>
    </div>
    <div class="hero-actions">
        <?php if ($role === 'student'): ?>
            <a class="button primary" href="registrations.php">Register course</a>
        <?php elseif (can_upload_teaching_files($role)): ?>
            <a class="button primary" href="materials.php">Upload material</a>
        <?php endif; ?>
        <a class="button quiet" href="profile.php">Profile</a>
    </div>
</section>

<section class="stats-grid">
    <?php foreach ($cards as $card): ?>
        <article class="stat-card">
            <span><?= e($card['label']) ?></span>
            <strong><?= e($card['value']) ?></strong>
            <small><?= e($card['meta']) ?></small>
        </article>
    <?php endforeach; ?>
</section>

<section class="dashboard-grid">
    <article class="panel">
        <div class="panel-heading">
            <h2><?= is_admin_role($role) || $role === 'department_head' ? 'Pending registrations' : 'Upcoming assignments' ?></h2>
            <a href="<?= is_admin_role($role) || $role === 'department_head' ? 'registrations.php' : 'assignments.php' ?>">View all</a>
        </div>
        <div class="list-stack">
            <?php $items = (is_admin_role($role) || $role === 'department_head') ? $registrations : $assignments; ?>
            <?php foreach ($items as $item): ?>
                <?php if (isset($item['student_name'])): ?>
                    <a class="list-item" href="registrations.php?search=<?= e(urlencode($item['student_name'])) ?>">
                        <span>
                            <strong><?= e($item['student_name']) ?></strong>
                            <small><?= e($item['course_title']) ?> - <?= e($item['semester'] ?? '') ?></small>
                        </span>
                        <span class="pill warning"><?= e($item['status']) ?></span>
                    </a>
                <?php else: ?>
                    <a class="list-item" href="assignments.php?search=<?= e(urlencode($item['title'])) ?>">
                        <span>
                            <strong><?= e($item['title']) ?></strong>
                            <small><?= e($item['module_name'] ?: $item['course_title']) ?> - <?= e($item['submission_type']) ?></small>
                        </span>
                        <span class="pill"><?= e(display_date($item['deadline'])) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($items === []): ?>
                <div class="mini-empty">Nothing needs attention right now.</div>
            <?php endif; ?>
        </div>
    </article>

    <article class="panel">
        <div class="panel-heading">
            <h2>Recent materials</h2>
            <a href="materials.php">Open library</a>
        </div>
        <div class="list-stack">
            <?php foreach ($materials as $material): ?>
                <a class="list-item" href="materials.php?search=<?= e(urlencode($material['file_name'])) ?>">
                    <span>
                        <strong><?= e($material['title'] ?? $material['file_name']) ?></strong>
                        <small><?= e($material['module_name'] ?: $material['course_title']) ?> - <?= e(display_datetime($material['date_uploaded'] ?? '')) ?></small>
                    </span>
                    <span class="pill soft"><?= e($material['file_type']) ?></span>
                </a>
            <?php endforeach; ?>
            <?php if ($materials === []): ?>
                <div class="mini-empty">No materials are visible for your role yet.</div>
            <?php endif; ?>
        </div>
    </article>
</section>

<section class="panel">
    <div class="panel-heading">
        <h2>Announcements</h2>
        <a href="announcements.php">Open board</a>
    </div>
    <div class="announcement-grid">
        <?php foreach ($announcements as $announcement): ?>
            <article class="announcement-card">
                <span><?= e(display_date($announcement['date_posted'])) ?> - <?= e($announcement['class_group']) ?></span>
                <h3><?= e($announcement['title']) ?></h3>
                <p><?= e($announcement['body']) ?></p>
                <small><?= e($announcement['author']) ?></small>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php
page_end();
