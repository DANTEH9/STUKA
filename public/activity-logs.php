<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_admin_role();

$repo = repository();
$filters = [
    'entity_type' => request_value('entity_type'),
    'search' => request_value('search'),
];
$logs = $repo->getActivityLogs($filters);
$pagination = paginate($logs, (int) request_value('page', '1'), app_config('items_per_page'));

page_start('Activity Logs', 'activity-logs');
?>
<section class="page-title-row">
    <div>
        <h2>Activity logs</h2>
        <p>Review system activity for account, academic, upload, and settings changes.</p>
    </div>
</section>

<section class="page-toolbar">
    <form method="get" class="filter-bar">
        <select name="entity_type" aria-label="Entity type">
            <option value="">All entities</option>
            <?php foreach (['users', 'departments', 'courses', 'modules', 'course_registrations', 'materials', 'assignments', 'results', 'settings'] as $entity): ?>
                <option value="<?= e($entity) ?>" <?= selected($filters['entity_type'], $entity) ?>><?= e($entity) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Search activity">
        <button class="button yellow" type="submit">Search</button>
    </form>
</section>

<section class="panel table-panel">
    <div class="table-actions"><span><?= e($pagination['total']) ?> events</span></div>
    <div class="responsive-table">
        <table>
            <thead><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>IP</th></tr></thead>
            <tbody>
                <?php foreach ($pagination['items'] as $log): ?>
                    <tr>
                        <td><?= e(display_datetime($log['created_at'] ?? '')) ?></td>
                        <td><strong><?= e($log['user_name'] ?? 'System') ?></strong><span><?= e($log['user_email'] ?? '') ?></span></td>
                        <td><?= e($log['action']) ?></td>
                        <td><span class="pill soft"><?= e($log['entity_type']) ?></span></td>
                        <td><?= e($log['ip_address'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
pagination_controls($pagination);
page_end();
