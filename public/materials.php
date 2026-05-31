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
        redirect('materials.php');
    }

    try {
        $upload = safe_uploaded_file('material_file', 'materials');
        $repo->createMaterial([
            'course_id' => post_value('course_id'),
            'module_id' => post_value('module_id'),
            'title' => post_value('title') ?: $upload['original_name'],
            'file_name' => $upload['original_name'],
            'file_path' => $upload['file_path'],
            'file_size' => $upload['file_size'],
            'file_type' => $upload['file_type'],
            'visibility' => post_value('visibility', 'course'),
        ], $user);
        set_flash('success', 'Study material uploaded.');
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
    }

    redirect('materials.php');
}

$courses = $repo->getCourseOptions();
$modules = $repo->getModuleOptions();
$filters = [
    'course_id' => request_value('course_id'),
    'file_type' => request_value('file_type'),
    'search' => request_value('search'),
];
$materials = $repo->getMaterials($filters, $user);
$pagination = paginate($materials, (int) request_value('page', '1'), app_config('items_per_page'));

page_start('Materials', 'materials');
?>
<section class="page-title-row">
    <div>
        <h2>Materials</h2>
        <p>Upload, browse, and download controlled course materials.</p>
    </div>
</section>

<?php if (can_upload_teaching_files($user['role'])): ?>
<section class="panel compact">
    <div class="panel-heading"><h2>Upload material</h2></div>
    <form class="form-grid" method="post" enctype="multipart/form-data">
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
            <span>Visibility</span>
            <select name="visibility"><option value="course">Course students</option><option value="public">All portal users</option></select>
        </label>
        <label class="file-input"><span>File</span><input type="file" name="material_file" required></label>
        <button class="button primary" type="submit">Upload material</button>
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
        <select name="file_type" aria-label="File type">
            <option value="">All file types</option>
            <?php foreach (['PDF', 'DOCX', 'PPTX', 'JPG', 'PNG'] as $type): ?>
                <option value="<?= e($type) ?>" <?= selected($filters['file_type'], $type) ?>><?= e($type) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Search files">
        <button class="button yellow" type="submit">Search</button>
    </form>
</section>

<section class="panel table-panel">
    <div class="table-actions">
        <span><?= e($pagination['total']) ?> files</span>
    </div>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Course</th>
                    <th>Module</th>
                    <th>Uploaded</th>
                    <th>Size</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagination['items'] as $material): ?>
                    <tr>
                        <td><strong><?= e($material['title'] ?? $material['file_name']) ?></strong><span><?= e($material['file_name']) ?> - <?= e($material['file_type']) ?></span></td>
                        <td><?= e($material['course_title']) ?></td>
                        <td><?= e($material['module_name'] ?: 'Whole course') ?></td>
                        <td><?= e(display_datetime($material['date_uploaded'] ?? '')) ?></td>
                        <td><?= e($material['file_size']) ?></td>
                        <td><a class="button yellow" href="<?= e($material['file_path'] ?? '#') ?>" download>Download</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
pagination_controls($pagination);
page_end();
