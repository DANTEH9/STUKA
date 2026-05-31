<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_academic_manager();

$repo = repository();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('lecturers.php');
    }

    try {
        $repo->createUser([
            'name' => post_value('name'),
            'email' => post_value('email'),
            'password' => post_value('password'),
            'role' => 'lecturer',
            'department_id' => post_value('department_id'),
            'status' => 'active',
            'staff_number' => post_value('staff_number'),
            'phone' => post_value('phone'),
            'program' => post_value('program'),
            'class_group' => 'Lecturer',
        ], $user);
        set_flash('success', 'Lecturer account created.');
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
    }

    redirect('lecturers.php');
}

$filters = ['search' => request_value('search'), 'department_id' => request_value('department_id')];
$lecturers = $repo->getLecturers($filters);
$pagination = paginate($lecturers, (int) request_value('page', '1'), app_config('items_per_page'));
$departments = $repo->getDepartmentOptions();

page_start('Lecturers', 'lecturers');
?>
<section class="page-title-row">
    <div>
        <h2>Lecturers</h2>
        <p>Manage teaching staff and department assignment records.</p>
    </div>
</section>

<section class="panel compact">
    <div class="panel-heading"><h2>Add lecturer</h2></div>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label><span>Name</span><input name="name" required></label>
        <label><span>Email</span><input type="email" name="email" required></label>
        <label><span>Password</span><input type="password" name="password" minlength="10" required></label>
        <label><span>Staff number</span><input name="staff_number" required></label>
        <label><span>Specialization</span><input name="program"></label>
        <label>
            <span>Department</span>
            <select name="department_id">
                <option value="">Select department</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= e($department['id']) ?>"><?= e($department['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><span>Phone</span><input name="phone"></label>
        <button class="button primary" type="submit">Create lecturer</button>
    </form>
</section>

<section class="page-toolbar">
    <form method="get" class="filter-bar">
        <select name="department_id" aria-label="Department">
            <option value="">All departments</option>
            <?php foreach ($departments as $department): ?>
                <option value="<?= e($department['id']) ?>" <?= selected($filters['department_id'], (string) $department['id']) ?>><?= e($department['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Search lecturers">
        <button class="button yellow" type="submit">Search</button>
    </form>
</section>

<section class="panel table-panel">
    <div class="table-actions"><span><?= e($pagination['total']) ?> lecturers</span></div>
    <div class="responsive-table">
        <table>
            <thead><tr><th>Lecturer</th><th>Department</th><th>Specialization</th><th>Status</th><th>Contact</th></tr></thead>
            <tbody>
                <?php foreach ($pagination['items'] as $lecturer): ?>
                    <tr>
                        <td><strong><?= e($lecturer['name']) ?></strong><span><?= e($lecturer['staff_number'] ?: $lecturer['email']) ?></span></td>
                        <td><?= e($lecturer['department_name'] ?? '') ?></td>
                        <td><?= e($lecturer['program'] ?? '') ?></td>
                        <td><span class="pill <?= ($lecturer['status'] ?? '') === 'active' ? 'success' : 'warning' ?>"><?= e($lecturer['status'] ?? '') ?></span></td>
                        <td><?= e($lecturer['phone'] ?: $lecturer['email']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
pagination_controls($pagination);
page_end();
