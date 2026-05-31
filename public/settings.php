<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_admin_role();

$repo = repository();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('settings.php');
    }

    try {
        foreach ($_POST['settings'] ?? [] as $key => $value) {
            $repo->updateSetting((string) $key, trim((string) $value), $user);
        }
        set_flash('success', 'Settings updated.');
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
    }

    redirect('settings.php');
}

$settings = $repo->getSettings();

page_start('Settings', 'settings');
?>
<section class="page-title-row">
    <div>
        <h2>Settings</h2>
        <p>Configure safe operational values without exposing database credentials in the codebase.</p>
    </div>
</section>

<section class="panel">
    <form method="post" class="settings-list">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php foreach ($settings as $setting): ?>
            <label class="setting-row">
                <span>
                    <strong><?= e($setting['setting_key']) ?></strong>
                    <small><?= e($setting['description'] ?? '') ?></small>
                </span>
                <input name="settings[<?= e($setting['setting_key']) ?>]" value="<?= e($setting['setting_value']) ?>">
            </label>
        <?php endforeach; ?>
        <button class="button primary" type="submit">Save settings</button>
    </form>
</section>
<?php
page_end();
