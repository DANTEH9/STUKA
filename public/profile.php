<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_login();

$user = current_user();

page_start('Profile', 'profile');
?>
<section class="page-title-row">
    <div>
        <h2>Profile</h2>
        <p>Your current account identity and access role.</p>
    </div>
</section>

<section class="profile-layout">
    <article class="panel profile-summary">
        <div class="avatar-initials huge"><?= e(initials($user['name'])) ?></div>
        <h2><?= e($user['name']) ?></h2>
        <span class="role-pill <?= e(role_badge_class($user['role'])) ?>"><?= e(role_label($user['role'])) ?></span>
    </article>
    <article class="panel">
        <div class="panel-heading"><h2>Account details</h2></div>
        <dl class="details-list">
            <div><dt>Email</dt><dd><?= e($user['email']) ?></dd></div>
            <div><dt>Status</dt><dd><?= e($user['status'] ?? 'active') ?></dd></div>
            <div><dt>Department</dt><dd><?= e($user['department_name'] ?? 'Not assigned') ?></dd></div>
            <div><dt>Program</dt><dd><?= e($user['program'] ?? 'Not set') ?></dd></div>
            <div><dt>Class group</dt><dd><?= e($user['class_group'] ?? 'Not set') ?></dd></div>
            <div><dt>Phone</dt><dd><?= e($user['phone'] ?? 'Not set') ?></dd></div>
        </dl>
    </article>
</section>
<?php
page_end();
