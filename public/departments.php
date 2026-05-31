<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_academic_manager();

$repo = repository();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('departments.php');
    }

    try {
        $repo->createDepartment([
            'name' => post_value('name'),
            'code' => post_value('code'),
            'description' => post_value('description'),
            'head_user_id' => post_value('head_user_id'),
        ], $user);
        set_flash('success', 'Department created.');
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
    }

    redirect('departments.php');
}

$filters = ['search' => request_value('search')];
$departments = $repo->getDepartments($filters);
$pagination = paginate($departments, (int) request_value('page', '1'), app_config('items_per_page'));
$heads = $repo->getLecturerOptions();

page_start('Departments', 'departments');
?>
<section class="page-title-row">
    <div>
        <h2>Departments</h2>
        <p>Maintain academic departments, heads, and the course ownership map.</p>
    </div>
</section>

<?php if (is_admin_role($user['role'])): ?>
<section class="panel compact">
    <div class="panel-heading"><h2>Add department</h2></div>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label><span>Code</span><input name="code" required maxlength="20"></label>
        <label><span>Name</span><input name="name" required></label>
        <label>
            <span>Department head</span>
            <select name="head_user_id">
                <option value="">Not assigned</option>
                <?php foreach ($heads as $head): ?>
                    <option value="<?= e($head['id']) ?>"><?= e($head['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="wide"><span>Description</span><textarea name="description" rows="3"></textarea></label>
        <button class="button primary" type="submit">Create department</button>
    </form>
</section>
<?php endif; ?>

<section class="page-toolbar">
    <form method="get" class="filter-bar">
        <input type="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Search departments">
        <button class="button yellow" type="submit">Search</button>
    </form>
</section>

<section class="panel table-panel">
    <div class="table-actions"><span><?= e($pagination['total']) ?> departments</span></div>
    <div class="responsive-table">
        <table>
            <thead><tr><th>Department</th><th>Head</th><th>Users</th><th>Courses</th><th>Description</th></tr></thead>
            <tbody>
                <?php foreach ($pagination['items'] as $department): ?>
                    <tr>
                        <td><strong><?= e($department['name']) ?></strong><span><?= e($department['code']) ?></span></td>
                        <td><?= e($department['head_name'] ?? 'Not assigned') ?></td>
                        <td><span class="pill soft"><?= e($department['user_count'] ?? 0) ?></span></td>
                        <td><span class="pill"><?= e($department['course_count'] ?? 0) ?></span></td>
                        <td><?= e($department['description'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
pagination_controls($pagination);
page_end();
