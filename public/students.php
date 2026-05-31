<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_academic_manager();

$repo = repository();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('students.php');
    }

    try {
        $repo->createUser([
            'name' => post_value('name'),
            'email' => post_value('email'),
            'password' => post_value('password'),
            'role' => 'student',
            'department_id' => post_value('department_id'),
            'status' => 'active',
            'student_number' => post_value('student_number'),
            'phone' => post_value('phone'),
            'program' => post_value('program'),
            'class_group' => post_value('class_group'),
        ], $user);
        set_flash('success', 'Student account created.');
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
    }

    redirect('students.php');
}

$filters = ['search' => request_value('search'), 'department_id' => request_value('department_id')];
$students = $repo->getStudents($filters);
$pagination = paginate($students, (int) request_value('page', '1'), app_config('items_per_page'));
$departments = $repo->getDepartmentOptions();

page_start('Students', 'students');
?>
<section class="page-title-row">
    <div>
        <h2>Students</h2>
        <p>Manage student profiles, class groups, departments, and registration identity.</p>
    </div>
</section>

<section class="panel compact">
    <div class="panel-heading"><h2>Add student</h2></div>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label><span>Name</span><input name="name" required></label>
        <label><span>Email</span><input type="email" name="email" required></label>
        <label><span>Password</span><input type="password" name="password" minlength="10" required></label>
        <label><span>Student number</span><input name="student_number" required></label>
        <label><span>Program</span><input name="program" required></label>
        <label><span>Class group</span><input name="class_group" required></label>
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
        <button class="button primary" type="submit">Create student</button>
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
        <input type="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Search students">
        <button class="button yellow" type="submit">Search</button>
    </form>
</section>

<section class="panel table-panel">
    <div class="table-actions"><span><?= e($pagination['total']) ?> students</span></div>
    <div class="responsive-table">
        <table>
            <thead><tr><th>Student</th><th>Program</th><th>Department</th><th>Class</th><th>Status</th><th>Contact</th></tr></thead>
            <tbody>
                <?php foreach ($pagination['items'] as $student): ?>
                    <tr>
                        <td><strong><?= e($student['name']) ?></strong><span><?= e($student['student_number'] ?: $student['email']) ?></span></td>
                        <td><?= e($student['program'] ?? '') ?></td>
                        <td><?= e($student['department_name'] ?? '') ?></td>
                        <td><span class="pill soft"><?= e($student['class_group'] ?? '') ?></span></td>
                        <td><span class="pill <?= ($student['status'] ?? '') === 'active' ? 'success' : 'warning' ?>"><?= e($student['status'] ?? '') ?></span></td>
                        <td><?= e($student['phone'] ?: $student['email']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
pagination_controls($pagination);
page_end();
