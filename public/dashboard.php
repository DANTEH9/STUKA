<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_login();

$user = current_user();
$repo = repository();
$stats = $repo->getStats($user);
$role = $user['role'];
$isAdminDashboard = is_admin_role($role) || $role === 'department_head';
$announcements = array_slice($repo->getAnnouncements([], $user), 0, 3);
$materials = array_slice($repo->getMaterials([], $user), 0, 4);
$registrations = array_slice($repo->getCourseRegistrations($isAdminDashboard ? ['status' => 'pending'] : [], $user), 0, 4);
$activityLogs = $isAdminDashboard ? array_slice($repo->getActivityLogs([]), 0, 5) : [];
$quickActions = [
    ['label' => 'Students', 'href' => 'students.php', 'meta' => 'Manage learner records'],
    ['label' => 'Class Reps', 'href' => 'class-representatives.php', 'meta' => 'Assign class representatives'],
    ['label' => 'Courses', 'href' => 'courses.php', 'meta' => 'Review course catalogue'],
    ['label' => 'Announcements', 'href' => 'announcements.php', 'meta' => 'Publish academic notices'],
];

$cardsByRole = [
    'super_admin' => [
        ['label' => 'Total Students', 'value' => $stats['total_students'], 'meta' => 'Active student records'],
        ['label' => 'Total Lecturers', 'value' => $stats['total_lecturers'], 'meta' => 'Teaching accounts'],
        ['label' => 'Total Modules', 'value' => $stats['total_modules'], 'meta' => 'Published module units'],
        ['label' => 'Total Departments', 'value' => $stats['total_departments'], 'meta' => 'Academic units'],
        ['label' => 'Active Courses', 'value' => $stats['active_courses'], 'meta' => 'Open programmes'],
    ],
    'admin' => [
        ['label' => 'Total Students', 'value' => $stats['total_students'], 'meta' => 'Managed learners'],
        ['label' => 'Total Lecturers', 'value' => $stats['total_lecturers'], 'meta' => 'Teaching accounts'],
        ['label' => 'Total Modules', 'value' => $stats['total_modules'], 'meta' => 'Published module units'],
        ['label' => 'Total Departments', 'value' => $stats['total_departments'], 'meta' => 'Academic units'],
        ['label' => 'Active Courses', 'value' => $stats['active_courses'], 'meta' => 'Registration catalogue'],
    ],
    'department_head' => [
        ['label' => 'Total Students', 'value' => $stats['total_students'], 'meta' => 'Visible learners'],
        ['label' => 'Total Lecturers', 'value' => $stats['total_lecturers'], 'meta' => 'Teaching staff'],
        ['label' => 'Total Modules', 'value' => $stats['total_modules'], 'meta' => 'Department module map'],
        ['label' => 'Total Departments', 'value' => $stats['total_departments'], 'meta' => 'Academic units'],
        ['label' => 'Active Courses', 'value' => $stats['active_courses'], 'meta' => 'Department coverage'],
    ],
    'lecturer' => [
        ['label' => 'Assigned courses', 'value' => $stats['assigned_courses'] ?? 0, 'meta' => 'Teaching load'],
        ['label' => 'Enrolled students', 'value' => $stats['enrolled_students'] ?? 0, 'meta' => 'Approved registrations'],
        ['label' => 'Materials', 'value' => $stats['materials'], 'meta' => 'Study files'],
        ['label' => 'Past papers', 'value' => $stats['past_papers'], 'meta' => 'Reference papers'],
    ],
    'student' => [
        ['label' => 'Registered courses', 'value' => $stats['registered_courses'] ?? 0, 'meta' => 'Approved courses'],
        ['label' => 'Materials', 'value' => $stats['materials'], 'meta' => 'Study files'],
        ['label' => 'Past papers', 'value' => $stats['past_papers'], 'meta' => 'Revision files'],
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

<section class="stats-grid dashboard-stat-grid">
    <?php foreach ($cards as $card): ?>
        <article class="stat-card">
            <span><?= e($card['label']) ?></span>
            <strong><?= e($card['value']) ?></strong>
            <small><?= e($card['meta']) ?></small>
        </article>
    <?php endforeach; ?>
</section>

<?php if ($isAdminDashboard): ?>
<section class="dashboard-grid admin-dashboard-grid">
    <article class="panel quick-actions-panel">
        <div class="panel-heading">
            <h2>Quick actions</h2>
            <a href="reports.php">Reports</a>
        </div>
        <div class="quick-action-list">
            <?php foreach ($quickActions as $action): ?>
                <a class="quick-action" href="<?= e($action['href']) ?>">
                    <strong><?= e($action['label']) ?></strong>
                    <span><?= e($action['meta']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel recent-activity-panel">
        <div class="panel-heading">
            <h2>Recent activities</h2>
            <a href="activity-logs.php">Open logs</a>
        </div>
        <div class="activity-timeline">
            <?php foreach ($activityLogs as $activity): ?>
                <a class="activity-item" href="activity-logs.php?search=<?= e(urlencode($activity['entity_type'] ?? '')) ?>">
                    <span></span>
                    <strong><?= e($activity['action']) ?></strong>
                    <small><?= e($activity['user_name'] ?? 'System') ?> - <?= e(display_datetime($activity['created_at'] ?? '')) ?></small>
                </a>
            <?php endforeach; ?>
            <?php if ($activityLogs === []): ?>
                <div class="mini-empty">No activity has been recorded yet.</div>
            <?php endif; ?>
        </div>
    </article>
</section>
<?php endif; ?>

<section class="dashboard-grid">
    <article class="panel">
        <div class="panel-heading">
            <h2><?= $isAdminDashboard ? 'Pending registrations' : 'Course registrations' ?></h2>
            <a href="registrations.php">View all</a>
        </div>
        <div class="list-stack">
            <?php $items = $registrations; ?>
            <?php foreach ($items as $item): ?>
                <a class="list-item" href="registrations.php?search=<?= e(urlencode($item['student_name'] ?? $item['course_title'] ?? '')) ?>">
                    <span>
                        <strong><?= e($item['student_name'] ?? $item['course_title'] ?? 'Registration') ?></strong>
                        <small><?= e($item['course_title'] ?? '') ?> - <?= e($item['semester'] ?? '') ?></small>
                    </span>
                    <span class="pill warning"><?= e($item['status'] ?? 'pending') ?></span>
                </a>
            <?php endforeach; ?>
            <?php if ($items === []): ?>
                <div class="mini-empty">No registration updates right now.</div>
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

<section class="panel dashboard-announcements">
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
