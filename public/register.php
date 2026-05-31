<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$repo = repository();
$departments = $repo->getDepartmentOptions();
$errors = [];
$form = [
    'name' => '',
    'email' => '',
    'student_number' => '',
    'program' => '',
    'class_group' => '',
    'department_id' => '',
    'phone' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'name' => post_value('name'),
        'email' => strtolower(post_value('email')),
        'student_number' => strtoupper(post_value('student_number')),
        'program' => post_value('program'),
        'class_group' => strtoupper(post_value('class_group')),
        'department_id' => post_value('department_id'),
        'phone' => post_value('phone'),
    ];
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $departmentIds = array_map('strval', array_column($departments, 'id'));

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    }

    if ($form['name'] === '') {
        $errors[] = 'Enter your full name.';
    }

    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    } elseif ($repo->findUserByEmail($form['email'])) {
        $errors[] = 'That email address is already registered.';
    }

    if ($form['student_number'] === '') {
        $errors[] = 'Enter your student number.';
    } elseif ($repo->studentNumberExists($form['student_number'])) {
        $errors[] = 'That student number is already registered.';
    }

    if ($form['program'] === '') {
        $errors[] = 'Enter your program.';
    }

    if ($form['class_group'] === '') {
        $errors[] = 'Enter your class group.';
    }

    if ($form['department_id'] === '' || !in_array($form['department_id'], $departmentIds, true)) {
        $errors[] = 'Choose your department.';
    }

    if (strlen($password) < 10) {
        $errors[] = 'Use a password with at least 10 characters.';
    } elseif ($password !== $passwordConfirm) {
        $errors[] = 'Password confirmation does not match.';
    }

    if ($errors === []) {
        try {
            $repo->createUser([
                'name' => $form['name'],
                'email' => $form['email'],
                'password' => $password,
                'role' => 'student',
                'department_id' => $form['department_id'],
                'status' => 'active',
                'student_number' => $form['student_number'],
                'phone' => $form['phone'],
                'program' => $form['program'],
                'class_group' => $form['class_group'],
            ]);

            if (login_user($form['email'], $password)) {
                set_flash('success', 'Student account created. You are now signed in.');
                redirect(home_after_login(current_user()));
            }

            set_flash('success', 'Student account created. Please sign in.');
            redirect('login.php');
        } catch (Throwable $error) {
            $errors[] = str_contains($error->getMessage(), 'Duplicate entry')
                ? 'That email address or student number is already registered.'
                : 'The account could not be created. Please check the database connection and try again.';
        }
    }
}

$config = app_config();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | <?= e($config['app_name']) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css?v=20260531-auth">
</head>
<body class="login-page">
    <main class="login-shell register-shell">
        <section class="login-panel">
            <div class="brand login-brand">
                <img src="assets/img/stuca-logo.png" alt="STUCA Student Course Assistance System">
            </div>

            <h1>Student Registration</h1>
            <p class="muted">Create your student account, then request course registration from the portal.</p>

            <?php if ($errors !== []): ?>
                <div class="notice error">
                    <strong>Registration needs a quick fix:</strong>
                    <ul class="notice-list">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="stacked-form register-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label class="wide">
                    <span>Full name</span>
                    <input name="name" value="<?= e($form['name']) ?>" autocomplete="name" required>
                </label>
                <label>
                    <span>Email address</span>
                    <input type="email" name="email" value="<?= e($form['email']) ?>" autocomplete="email" required>
                </label>
                <label>
                    <span>Student number</span>
                    <input name="student_number" value="<?= e($form['student_number']) ?>" required>
                </label>
                <label>
                    <span>Program</span>
                    <input name="program" value="<?= e($form['program']) ?>" required>
                </label>
                <label>
                    <span>Class group</span>
                    <input name="class_group" value="<?= e($form['class_group']) ?>" required>
                </label>
                <label>
                    <span>Department</span>
                    <select name="department_id" required>
                        <option value="">Select department</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= e($department['id']) ?>" <?= selected($form['department_id'], (string) $department['id']) ?>>
                                <?= e($department['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Phone</span>
                    <input name="phone" value="<?= e($form['phone']) ?>" autocomplete="tel">
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" minlength="10" autocomplete="new-password" required>
                </label>
                <label>
                    <span>Confirm password</span>
                    <input type="password" name="password_confirm" minlength="10" autocomplete="new-password" required>
                </label>
                <button class="button primary full wide" type="submit">Register student account</button>
            </form>

            <p class="auth-link">Already have an account? <a href="login.php">Back to login</a></p>
        </section>
    </main>
</body>
</html>
