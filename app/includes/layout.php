<?php

declare(strict_types=1);

function nav_items(): array
{
    $role = current_user()['role'] ?? 'student';
    $items = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => 'dashboard', 'roles' => ['super_admin', 'admin', 'department_head', 'lecturer', 'student']],
        ['key' => 'users', 'label' => 'Users', 'href' => 'users.php', 'icon' => 'users', 'roles' => ['super_admin']],
        ['key' => 'students', 'label' => 'Students', 'href' => 'students.php', 'icon' => 'student', 'roles' => ['super_admin', 'admin', 'department_head']],
        ['key' => 'lecturers', 'label' => 'Lecturers', 'href' => 'lecturers.php', 'icon' => 'lecturer', 'roles' => ['super_admin', 'admin', 'department_head']],
        ['key' => 'departments', 'label' => 'Departments', 'href' => 'departments.php', 'icon' => 'department', 'roles' => ['super_admin', 'admin', 'department_head']],
        ['key' => 'courses', 'label' => 'Courses', 'href' => 'courses.php', 'icon' => 'course', 'roles' => ['super_admin', 'admin', 'department_head', 'lecturer', 'student']],
        ['key' => 'modules', 'label' => 'Modules', 'href' => 'modules.php', 'icon' => 'book', 'roles' => ['super_admin', 'admin', 'department_head', 'lecturer', 'student']],
        ['key' => 'registrations', 'label' => 'Registrations', 'href' => 'registrations.php', 'icon' => 'check', 'roles' => ['super_admin', 'admin', 'department_head', 'lecturer', 'student']],
        ['key' => 'materials', 'label' => 'Materials', 'href' => 'materials.php', 'icon' => 'folder', 'roles' => ['super_admin', 'admin', 'lecturer', 'student']],
        ['key' => 'assignments', 'label' => 'Assignments', 'href' => 'assignments.php', 'icon' => 'clipboard', 'roles' => ['super_admin', 'admin', 'lecturer', 'student']],
        ['key' => 'timetable', 'label' => 'Timetable', 'href' => 'timetable.php', 'icon' => 'calendar', 'roles' => ['super_admin', 'admin', 'department_head', 'lecturer', 'student']],
        ['key' => 'results', 'label' => 'Results', 'href' => 'results.php', 'icon' => 'chart', 'roles' => ['super_admin', 'admin', 'department_head', 'lecturer', 'student']],
        ['key' => 'past-papers', 'label' => 'Past Papers', 'href' => 'past-papers.php', 'icon' => 'archive', 'roles' => ['super_admin', 'admin', 'lecturer', 'student']],
        ['key' => 'announcements', 'label' => 'Announcements', 'href' => 'announcements.php', 'icon' => 'signal', 'roles' => ['super_admin', 'admin', 'department_head', 'lecturer', 'student']],
        ['key' => 'reports', 'label' => 'Reports', 'href' => 'reports.php', 'icon' => 'report', 'roles' => ['super_admin', 'admin', 'department_head']],
        ['key' => 'activity-logs', 'label' => 'Activity Logs', 'href' => 'activity-logs.php', 'icon' => 'activity', 'roles' => ['super_admin', 'admin']],
        ['key' => 'settings', 'label' => 'Settings', 'href' => 'settings.php', 'icon' => 'settings', 'roles' => ['super_admin', 'admin']],
        ['key' => 'profile', 'label' => 'Profile', 'href' => 'profile.php', 'icon' => 'profile', 'roles' => ['super_admin', 'admin', 'department_head', 'lecturer', 'student']],
    ];

    return array_values(array_filter($items, static fn (array $item): bool => in_array($role, $item['roles'], true)));
}

function nav_icon(string $icon): string
{
    $paths = [
        'dashboard' => '<path d="M4 11.2 12 5l8 6.2v7.1a1.7 1.7 0 0 1-1.7 1.7H5.7A1.7 1.7 0 0 1 4 18.3v-7.1Z"/><path d="M9.5 20v-6h5v6"/>',
        'calendar' => '<path d="M6.5 4.5v3M17.5 4.5v3M4.5 9.5h15"/><path d="M6.5 6h11A2.5 2.5 0 0 1 20 8.5v9A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-9A2.5 2.5 0 0 1 6.5 6Z"/>',
        'book' => '<path d="M5.5 5.5A2.5 2.5 0 0 1 8 3h11v15.5H8a2.5 2.5 0 0 0-2.5 2.5V5.5Z"/><path d="M9 7h6M9 10h4"/>',
        'folder' => '<path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h4l2 2.5h5A2.5 2.5 0 0 1 20 10v6.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5v-9Z"/><path d="M4 10h16"/>',
        'clipboard' => '<path d="M9 5.5A2.5 2.5 0 0 1 11.5 3h1A2.5 2.5 0 0 1 15 5.5V6H9v-.5Z"/><path d="M8 5H6.5A2.5 2.5 0 0 0 4 7.5v11A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5v-11A2.5 2.5 0 0 0 17.5 5H16"/>',
        'chart' => '<path d="M5 19V9M12 19V5M19 19v-7"/><path d="M3.5 20.5h17"/>',
        'archive' => '<path d="M5 7h14v12.5A1.5 1.5 0 0 1 17.5 21h-11A1.5 1.5 0 0 1 5 19.5V7Z"/><path d="M4 3h16v4H4zM9 11h6"/>',
        'signal' => '<path d="M6 15.5a8 8 0 0 1 12 0"/><path d="M8.8 18.2a4.4 4.4 0 0 1 6.4 0"/><path d="M12 21h.01"/>',
        'users' => '<path d="M16 19.5v-1.1c0-1.9-1.9-3.4-4-3.4s-4 1.5-4 3.4v1.1"/><path d="M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M20 19v-1c0-1.4-1.2-2.6-2.8-3.1M17 5.5a2.5 2.5 0 0 1 0 5"/>',
        'student' => '<path d="m3.5 8.5 8.5-4 8.5 4-8.5 4-8.5-4Z"/><path d="M6.5 10.5v4.2c0 1.9 2.5 3.3 5.5 3.3s5.5-1.4 5.5-3.3v-4.2"/><path d="M20.5 9v5"/>',
        'lecturer' => '<path d="M5 5h14v10H5z"/><path d="M8 19h8M12 15v4"/><path d="M8 9h8M8 12h5"/>',
        'department' => '<path d="M4 20V7l8-4 8 4v13"/><path d="M8 20v-6h8v6M8 9h.01M12 9h.01M16 9h.01"/>',
        'course' => '<path d="M5 4h10a4 4 0 0 1 4 4v12H8a3 3 0 0 1-3-3V4Z"/><path d="M8 4v13a3 3 0 0 1 3-3h8"/><path d="M9 8h5"/>',
        'check' => '<path d="M5 12.5 9.5 17 19 7"/><path d="M4 4h16v16H4z"/>',
        'report' => '<path d="M6 3h9l3 3v15H6z"/><path d="M14 3v4h4M9 14h6M9 17h4M9 10h6"/>',
        'activity' => '<path d="M4 12h4l2.2-5 3.6 10 2.2-5h4"/><path d="M4 4h16v16H4z"/>',
        'settings' => '<path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/><path d="M19.4 15a1.8 1.8 0 0 0 .36 2l.05.05-2.1 2.1-.06-.05a1.8 1.8 0 0 0-2-.36 1.8 1.8 0 0 0-1.1 1.66V20h-3v-.08a1.8 1.8 0 0 0-1.1-1.66 1.8 1.8 0 0 0-2 .36l-.06.05-2.1-2.1.05-.05a1.8 1.8 0 0 0 .36-2A1.8 1.8 0 0 0 5 13.4H4v-3h1a1.8 1.8 0 0 0 1.66-1.1 1.8 1.8 0 0 0-.36-2l-.05-.05 2.1-2.1.06.05a1.8 1.8 0 0 0 2 .36A1.8 1.8 0 0 0 11.5 4V3h3v1a1.8 1.8 0 0 0 1.1 1.66 1.8 1.8 0 0 0 2-.36l.06-.05 2.1 2.1-.05.05a1.8 1.8 0 0 0-.36 2A1.8 1.8 0 0 0 21 10.5h1v3h-1a1.8 1.8 0 0 0-1.6 1.5Z"/>',
        'profile' => '<path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0"/>',
        'logout' => '<path d="M9.5 5H6.8A1.8 1.8 0 0 0 5 6.8v10.4A1.8 1.8 0 0 0 6.8 19h2.7"/><path d="M14 8l4 4-4 4M18 12H9"/>',
    ];

    $path = $paths[$icon] ?? $paths['dashboard'];

    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

function page_start(string $title, string $active): void
{
    $user = current_user();
    if ($user && !empty($user['email'])) {
        $freshUser = repository()->findUserByEmail($user['email']);
        if ($freshUser) {
            unset($freshUser['password_hash']);
            $_SESSION['user'] = $freshUser;
            $user = $freshUser;
        }
    }

    $config = app_config();
    $displayName = $user['name'] ?? 'Guest';
    $role = role_label($user['role'] ?? null);
    $messages = flash_messages();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="STUCA academic portal for course registration, teaching workflows, student progress, and campus resources.">
        <meta property="og:title" content="<?= e($title) ?> | <?= e($config['app_name']) ?>">
        <meta property="og:description" content="A PHP and MySQL academic portal for students, lecturers, administrators, and departments.">
        <title><?= e($title) ?> | <?= e($config['app_name']) ?></title>
        <link rel="stylesheet" href="assets/css/styles.css?v=20260601-workflow">
    </head>
    <body class="page-<?= e($active) ?>">
        <a class="skip-link" href="#content">Skip to content</a>
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="dashboard.php" aria-label="STUCA dashboard">
                <img src="assets/img/stuca-logo.png" alt="STUCA Student Course Assistance System">
                <span><?= e($config['app_subtitle']) ?></span>
            </a>

            <div class="profile-card">
                <div class="avatar-initials"><?= e(initials($displayName)) ?></div>
                <strong><?= e($displayName) ?></strong>
                <span class="role-pill <?= e(role_badge_class($user['role'] ?? null)) ?>"><?= e($role) ?></span>
            </div>

            <nav class="nav-list" aria-label="Primary navigation">
                <?php foreach (nav_items() as $item): ?>
                    <a class="<?= e(active_class($active, $item['key'])) ?>" href="<?= e($item['href']) ?>">
                        <span class="nav-icon"><?= nav_icon($item['icon']) ?></span>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
                <a href="logout.php">
                    <span class="nav-icon accent"><?= nav_icon('logout') ?></span>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <div class="app-shell">
            <header class="topbar">
                <button class="icon-button" type="button" data-sidebar-toggle aria-label="Toggle navigation">
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <p><?= e($role) ?></p>
                    <h1><?= e($title) ?></h1>
                </div>
                <div class="topbar-user">
                    <span class="topbar-name"><?= e($displayName) ?></span>
                    <a class="avatar-initials tiny" href="profile.php" aria-label="Open profile"><?= e(initials($displayName)) ?></a>
                </div>
            </header>

            <main class="content" id="content">
                <?php foreach ($messages as $type => $message): ?>
                    <div class="notice <?= e($type) ?>"><?= e($message) ?></div>
                <?php endforeach; ?>
    <?php
}

function page_end(): void
{
    ?>
            </main>
        </div>
        <script src="assets/js/app.js?v=20260601-workflow"></script>
    </body>
    </html>
    <?php
}

function pagination_controls(array $pagination): void
{
    $page = $pagination['page'];
    $pages = $pagination['pages'];
    ?>
    <div class="pagination">
        <a class="home-link" href="dashboard.php">Home</a>
        <div class="page-links">
            <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="?<?= e(build_query(['page' => max(1, $page - 1)])) ?>">Prev</a>
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a class="<?= $page === $i ? 'current' : '' ?>" href="?<?= e(build_query(['page' => $i])) ?>"><?= e($i) ?></a>
            <?php endfor; ?>
            <a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="?<?= e(build_query(['page' => min($pages, $page + 1)])) ?>">Next</a>
        </div>
        <span><?= e((string) $pagination['total']) ?> records</span>
    </div>
    <?php
}

function empty_state(string $title, string $body, string $actionHref = '', string $actionLabel = ''): void
{
    ?>
    <section class="empty-state">
        <strong>No records</strong>
        <h2><?= e($title) ?></h2>
        <p><?= e($body) ?></p>
        <?php if ($actionHref !== '' && $actionLabel !== ''): ?>
            <a class="button primary" href="<?= e($actionHref) ?>"><?= e($actionLabel) ?></a>
        <?php endif; ?>
    </section>
    <?php
}
