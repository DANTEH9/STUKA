<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_role(['super_admin']);

$repo = repository();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('users.php');
    }

    try {
        $action = post_value('action');
        if ($action === 'create') {
            $repo->createUser([
                'name' => post_value('name'),
                'email' => post_value('email'),
                'password' => post_value('password'),
                'role' => post_value('role', 'student'),
                'department_id' => post_value('department_id'),
                'status' => post_value('status', 'active'),
                'student_number' => post_value('student_number'),
                'staff_number' => post_value('staff_number'),
                'phone' => post_value('phone'),
                'program' => post_value('program'),
                'class_group' => post_value('class_group'),
            ], $user);
            set_flash('success', 'User account created.');
        } elseif ($action === 'status') {
            $repo->updateUserStatus((int) post_value('user_id'), post_value('status', 'active'), $user);
            set_flash('success', 'User status updated.');
        } elseif ($action === 'delete') {
            $targetId = (int) post_value('user_id');
            if ($targetId === (int) $user['id']) {
                set_flash('error', 'You cannot delete your own account while signed in.');
            } else {
                $repo->deleteUser($targetId, $user);
                set_flash('success', 'User account deleted.');
            }
        }
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
    }

    redirect('users.php');
}

$filters = [
    'role' => request_value('role'),
    'status' => request_value('status'),
    'search' => request_value('search'),
];
$users = $repo->getUsers($filters);
$pagination = paginate($users, (int) request_value('page', '1'), app_config('items_per_page'));
$roles = $repo->getRoleOptions();
$departments = $repo->getDepartmentOptions();

page_start('Users', 'users');
?>
<section class="page-title-row">
    <div>
        <h2>User administration</h2>
        <p>Create, suspend, reactivate, and remove portal accounts with role assignments.</p>
    </div>
</section>

<section class="panel compact">
    <div class="panel-heading">
        <h2>Add user</h2>
    </div>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create">
        <label><span>Name</span><input name="name" required></label>
        <label><span>Email</span><input type="email" name="email" required></label>
        <label><span>Password</span><input type="password" name="password" minlength="10" required></label>
        <label>
            <span>Role</span>
            <select name="role" required>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= e($role['slug']) ?>"><?= e($role['name']) ?></option>
                <?php endforeach; ?>
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
        <label><span>Program</span><input name="program"></label>
        <label><span>Class group</span><input name="class_group"></label>
        <label><span>Student number</span><input name="student_number"></label>
        <label><span>Staff number</span><input name="staff_number"></label>
        <label><span>Phone</span><input name="phone"></label>
        <label>
            <span>Status</span>
            <select name="status">
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
            </select>
        </label>
        <button class="button primary" type="submit">Create user</button>
    </form>
</section>

<section class="page-toolbar">
    <form method="get" class="filter-bar">
        <select name="role" aria-label="Role">
            <option value="">All roles</option>
            <?php foreach ($roles as $role): ?>
                <option value="<?= e($role['slug']) ?>" <?= selected($filters['role'], $role['slug']) ?>><?= e($role['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" aria-label="Status">
            <option value="">All statuses</option>
            <option value="active" <?= selected($filters['status'], 'active') ?>>Active</option>
            <option value="suspended" <?= selected($filters['status'], 'suspended') ?>>Suspended</option>
        </select>
        <input type="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Search users">
        <button class="button yellow" type="submit">Search</button>
    </form>
</section>

<section class="panel table-panel">
    <div class="table-actions">
        <span><?= e($pagination['total']) ?> users</span>
    </div>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Identifiers</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagination['items'] as $record): ?>
                    <tr>
                        <td>
                            <strong><?= e($record['name']) ?></strong>
                            <span><?= e($record['email']) ?></span>
                        </td>
                        <td><span class="pill <?= e(role_badge_class($record['role'])) ?>"><?= e($record['role_name'] ?? role_label($record['role'])) ?></span></td>
                        <td><?= e($record['department_name'] ?? 'None') ?></td>
                        <td><span class="pill <?= $record['status'] === 'active' ? 'success' : 'warning' ?>"><?= e($record['status']) ?></span></td>
                        <td>
                            <strong><?= e($record['student_number'] ?: $record['staff_number'] ?: 'Not set') ?></strong>
                            <span><?= e($record['class_group'] ?? '') ?></span>
                        </td>
                        <td class="action-cell">
                            <form method="post" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="user_id" value="<?= e($record['id']) ?>">
                                <input type="hidden" name="status" value="<?= $record['status'] === 'active' ? 'suspended' : 'active' ?>">
                                <button class="button subtle" type="submit"><?= $record['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
                            </form>
                            <form method="post" class="inline-form" data-confirm="Delete this account?">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= e($record['id']) ?>">
                                <button class="button danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
pagination_controls($pagination);
page_end();
