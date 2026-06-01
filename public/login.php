<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (!login_user(trim((string) ($_POST['email'] ?? '')), (string) ($_POST['password'] ?? ''))) {
        $error = 'Invalid email or password.';
    } else {
        redirect(home_after_login(current_user()));
    }
}

$config = app_config();
$messages = flash_messages();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | <?= e($config['app_name']) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css?v=20260601-workflow">
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-panel">
            <div class="brand login-brand">
                <img src="assets/img/stuca-logo.png" alt="STUCA Student Course Assistance System">
            </div>

            <h1>Academic Portal</h1>
            <p class="muted">Sign in to access modules, study materials, results, and past papers.</p>

            <?php if ($error !== ''): ?>
                <div class="notice error"><?= e($error) ?></div>
            <?php endif; ?>

            <?php foreach ($messages as $type => $message): ?>
                <div class="notice <?= e($type) ?>"><?= e($message) ?></div>
            <?php endforeach; ?>

            <form method="post" class="stacked-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label>
                    <span>Email address</span>
                    <input type="email" name="email" value="student@stuca.local" autocomplete="email" required>
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" value="StucaStudent@2026" autocomplete="current-password" required>
                </label>
                <button class="button primary full" type="submit">Login</button>
            </form>

            <div class="auth-actions">
                <a class="button yellow full" href="register.php">Create student account</a>
            </div>

            <div class="demo-logins">
                <span>Super: super@stuca.local / StucaSuper@2026</span>
                <span>Admin: admin@stuca.local / StucaAdmin@2026</span>
                <span>Lecturer: lecturer@stuca.local / StucaLecturer@2026</span>
                <span>Student: student@stuca.local / StucaStudent@2026</span>
            </div>
        </section>
    </main>
</body>
</html>
