<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_login();

$repo = repository();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role(['super_admin', 'admin', 'department_head', 'lecturer']);
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('announcements.php');
    }

    try {
        $repo->createAnnouncement([
            'title' => post_value('title'),
            'body' => post_value('body'),
            'audience' => post_value('audience', 'global'),
            'department_id' => post_value('department_id'),
            'course_id' => post_value('course_id'),
            'module_id' => post_value('module_id'),
            'publish_at' => post_value('publish_at'),
        ], $user);
        set_flash('success', 'Announcement posted.');
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
    }

    redirect('announcements.php');
}

$courses = $repo->getCourseOptions();
$departments = $repo->getDepartmentOptions();
$modules = $repo->getModuleOptions();
$filters = [
    'class_group' => request_value('class_group'),
    'audience' => request_value('audience'),
    'search' => request_value('search'),
];
$announcements = $repo->getAnnouncements($filters, $user);
$pagination = paginate($announcements, (int) request_value('page', '1'), app_config('items_per_page'));

page_start('Announcements', 'announcements');
?>
<section class="page-title-row">
    <div>
        <h2>Announcements</h2>
        <p>Publish global, department, and course notices to the right audience.</p>
    </div>
</section>

<?php if (in_array($user['role'], ['super_admin', 'admin', 'department_head', 'lecturer'], true)): ?>
<section class="panel compact">
    <div class="panel-heading"><h2>Post announcement</h2></div>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label><span>Title</span><input name="title" required></label>
        <label>
            <span>Audience</span>
            <select name="audience">
                <option value="global">Global</option>
                <option value="department">Department</option>
                <option value="course">Course</option>
            </select>
        </label>
        <label>
            <span>Department</span>
            <select name="department_id">
                <option value="">None</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= e($department['id']) ?>"><?= e($department['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Course</span>
            <select name="course_id">
                <option value="">None</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course['id']) ?>"><?= e($course['code']) ?> - <?= e($course['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Module</span>
            <select name="module_id">
                <option value="">None</option>
                <?php foreach ($modules as $module): ?>
                    <option value="<?= e($module['id']) ?>"><?= e($module['code']) ?> - <?= e($module['title'] ?? $module['module_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><span>Publish at</span><input type="datetime-local" name="publish_at"></label>
        <label class="wide"><span>Message</span><textarea name="body" rows="4" required></textarea></label>
        <button class="button primary" type="submit">Post announcement</button>
    </form>
</section>
<?php endif; ?>

<section class="page-toolbar">
    <form method="get" class="filter-bar">
        <select name="audience" aria-label="Audience">
            <option value="">All audiences</option>
            <option value="global" <?= selected($filters['audience'], 'global') ?>>Global</option>
            <option value="department" <?= selected($filters['audience'], 'department') ?>>Department</option>
            <option value="course" <?= selected($filters['audience'], 'course') ?>>Course</option>
        </select>
        <input type="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Search announcements">
        <button class="button yellow" type="submit">Search</button>
    </form>
</section>

<section class="announcement-grid full">
    <?php foreach ($pagination['items'] as $announcement): ?>
        <article class="announcement-card panel">
            <span><?= e(display_date($announcement['date_posted'])) ?> - <?= e($announcement['class_group']) ?></span>
            <h2><?= e($announcement['title']) ?></h2>
            <p><?= e($announcement['body']) ?></p>
            <small><?= e($announcement['author']) ?></small>
        </article>
    <?php endforeach; ?>
</section>
<?php
if ($pagination['items'] === []) {
    empty_state('No announcements found', 'Try a different filter or post a new notice if your role allows it.');
}
pagination_controls($pagination);
page_end();
