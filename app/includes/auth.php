<?php

declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function login_user(string $email, string $password): bool
{
    $user = repository()->findUserByEmail($email);

    if (!$user || ($user['status'] ?? 'active') !== 'active' || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    unset($user['password_hash']);
    session_regenerate_id(true);
    $_SESSION['user'] = $user;

    return true;
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function require_role(array $roles): void
{
    require_login();

    $user = current_user();

    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        page_start('Access denied', 'forbidden');
        echo '<section class="empty-state"><strong>403</strong><h2>Access denied</h2><p>Your account does not have permission to open this page.</p><a class="button primary" href="dashboard.php">Back to dashboard</a></section>';
        page_end();
        exit;
    }
}

function require_admin_role(): void
{
    require_role(['super_admin', 'admin']);
}

function require_academic_manager(): void
{
    require_role(['super_admin', 'admin', 'department_head']);
}

function home_after_login(array $user): string
{
    return 'dashboard.php';
}
