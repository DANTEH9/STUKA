<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_login();

$repo = repository();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_academic_manager();
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('modules.php');
    }

    try {
        $repo->createModule([
            'course_id' => post_value('course_id'),
            'semester_id' => post_value('semester_id'),
            'code' => post_value('code'),
            'title' => post_value('title'),
            'credits' => post_value('credits', '3'),
            'description' => post_value('description'),
        ], $user);
        set_flash('success', 'Module created.');
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
    }

    redirect('modules.php');
}

$courses = $repo->getCourseOptions();
$semesters = $repo->getSemesterOptions();
$filters = [
    'course_id' => request_value('course_id'),
    'semester' => request_value('semester'),
    'search' => request_value('search'),
];
$modules = $repo->getModules($filters);
$pagination = paginate($modules, (int) request_value('page', '1'), app_config('items_per_page'));
$caResultsByModule = [];
$caModals = [];

if ($user['role'] === 'student') {
    foreach ($repo->getCaResultsForStudent((int) $user['id']) as $caResult) {
        $caResultsByModule[(int) $caResult['module_id']] = $caResult;
    }
}

page_start('Modules', 'modules');
?>
<section class="page-title-row">
    <div>
        <h2>Modules</h2>
        <p>Connect course content to semesters, lecturers, materials, and results.</p>
    </div>
</section>

<?php if (can_manage_academics($user['role'])): ?>
<section class="panel compact">
    <div class="panel-heading"><h2>Add module</h2></div>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label><span>Code</span><input name="code" required maxlength="30"></label>
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
            <span>Semester</span>
            <select name="semester_id">
                <?php foreach ($semesters as $semester): ?>
                    <option value="<?= e($semester['id']) ?>"><?= e($semester['academic_year'] ?? '') ?> <?= e($semester['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><span>Credits</span><input type="number" min="1" max="30" name="credits" value="3"></label>
        <label class="wide"><span>Description</span><textarea name="description" rows="3"></textarea></label>
        <button class="button primary" type="submit">Create module</button>
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
        <input type="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Search modules">
        <button class="button yellow" type="submit">Search</button>
    </form>
</section>

<section class="panel table-panel">
    <div class="table-actions">
        <span><?= e($pagination['total']) ?> modules</span>
    </div>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Course</th>
                    <th>Lecturer</th>
                    <th>Semester</th>
                    <th>Credits</th>
                    <?php if ($user['role'] === 'student'): ?>
                        <th>CA Results</th>
                    <?php endif; ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagination['items'] as $module): ?>
                    <?php $caResult = $caResultsByModule[(int) $module['id']] ?? null; ?>
                    <tr>
                        <td>
                            <strong><?= e($module['module_name']) ?> <small>(<?= e($module['code']) ?>)</small></strong>
                            <span><?= e($module['description']) ?></span>
                        </td>
                        <td>
                            <strong><?= e($module['course_title'] ?? '') ?></strong>
                            <span><?= e($module['course_code'] ?? '') ?></span>
                        </td>
                        <td><?= e($module['lecturer']) ?></td>
                        <td><?= e($module['semester']) ?></td>
                        <td><?= e($module['credits']) ?></td>
                        <?php if ($user['role'] === 'student'): ?>
                            <td>
                                <?php if ($caResult): ?>
                                    <?php $modalId = 'ca-modal-' . (int) $caResult['id']; $caModals[$modalId] = $caResult; ?>
                                    <button class="button subtle" type="button" data-modal-open="<?= e($modalId) ?>">
                                        <?= e((float) $caResult['total_ca']) ?>/<?= e((float) $caResult['max_ca']) ?> - View CA Results
                                    </button>
                                <?php else: ?>
                                    <span class="muted-text">Not released</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td class="action-cell">
                            <a class="button quiet" href="materials.php?search=<?= e(urlencode($module['module_name'])) ?>">Materials</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php foreach ($caModals as $modalId => $caResult): ?>
    <div class="modal-backdrop" id="<?= e($modalId) ?>" data-modal hidden>
        <section class="modal-card">
            <div class="modal-header">
                <div>
                    <h2><?= e($caResult['module_name']) ?> CA Breakdown</h2>
                    <span class="table-note"><?= e($caResult['course_code']) ?> - <?= e($caResult['class_group'] ?: ($user['class_group'] ?? 'Class')) ?> - <?= e($caResult['semester'] ?? '') ?></span>
                </div>
                <button class="button quiet" type="button" data-modal-close>Close</button>
            </div>
            <div class="modal-body">
                <div class="ca-grid">
                    <?php foreach ($caResult['items'] as $item): ?>
                        <div class="ca-row">
                            <span><?= e($item['item_name']) ?></span>
                            <strong><?= e((float) $item['score']) ?>/<?= e((float) $item['max_score']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                    <div class="ca-row total">
                        <span>Total CA</span>
                        <strong><?= e((float) $caResult['total_ca']) ?>/<?= e((float) $caResult['max_ca']) ?></strong>
                    </div>
                </div>
                <p class="muted modal-note"><?= e($caResult['lecturer_remarks'] ?: 'No lecturer remarks recorded.') ?></p>
            </div>
        </section>
    </div>
<?php endforeach; ?>
<?php
pagination_controls($pagination);
page_end();
