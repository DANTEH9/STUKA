<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function load_env_file(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function request_value(string $key, string $default = ''): string
{
    return trim((string) ($_GET[$key] ?? $default));
}

function post_value(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function flash_messages(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    if (!empty($_SESSION['database_notice'])) {
        $messages['info'] = $_SESSION['database_notice'];
        unset($_SESSION['database_notice']);
    }

    return $messages;
}

function selected(string $actual, string $expected): string
{
    return $actual === $expected ? 'selected' : '';
}

function checked(bool $value): string
{
    return $value ? 'checked' : '';
}

function active_class(string $active, string $page): string
{
    return $active === $page ? 'is-active' : '';
}

function role_label(?string $role): string
{
    return match ($role) {
        'super_admin' => 'Super Admin',
        'department_head' => 'Department Head',
        'admin' => 'Admin',
        'lecturer' => 'Lecturer',
        'student' => 'Student',
        default => 'Visitor',
    };
}

function role_badge_class(?string $role): string
{
    return match ($role) {
        'super_admin' => 'danger',
        'admin' => 'primary',
        'department_head' => 'accent',
        'lecturer' => 'success',
        'student' => 'soft',
        default => 'soft',
    };
}

function is_admin_role(?string $role = null): bool
{
    $role ??= current_user()['role'] ?? null;

    return in_array($role, ['super_admin', 'admin'], true);
}

function can_manage_academics(?string $role = null): bool
{
    $role ??= current_user()['role'] ?? null;

    return in_array($role, ['super_admin', 'admin', 'department_head'], true);
}

function can_upload_teaching_files(?string $role = null): bool
{
    $role ??= current_user()['role'] ?? null;

    return in_array($role, ['super_admin', 'admin', 'lecturer'], true);
}

function can_manage_results(?string $role = null): bool
{
    $role ??= current_user()['role'] ?? null;

    return in_array($role, ['super_admin', 'admin', 'lecturer'], true);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= strtoupper(substr($part, 0, 1));
    }

    return $letters !== '' ? $letters : 'ST';
}

function display_date(?string $date): string
{
    if (!$date) {
        return 'Not set';
    }

    $time = strtotime($date);
    return $time ? date('d M Y', $time) : $date;
}

function display_datetime(?string $date): string
{
    if (!$date) {
        return 'Not set';
    }

    $time = strtotime($date);
    return $time ? date('d M Y, H:i', $time) : $date;
}

function human_file_size(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }

    if ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }

    return $bytes . ' B';
}

function safe_uploaded_file(string $field, string $category): array
{
    $config = app_config();
    $file = $_FILES[$field] ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Choose a valid file before submitting.');
    }

    if ((int) $file['size'] > (int) $config['upload_max_bytes']) {
        throw new RuntimeException('The file is larger than the configured upload limit.');
    }

    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $config['allowed_upload_extensions'], true)) {
        throw new RuntimeException('This file type is not allowed.');
    }

    $baseName = pathinfo((string) $file['name'], PATHINFO_FILENAME);
    $safeBase = preg_replace('/[^A-Za-z0-9._-]+/', '-', $baseName) ?: 'upload';
    $storedName = trim($safeBase, '-_.') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
    $relativeDir = 'uploads/' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $category);
    $targetDir = PUBLIC_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);

    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        throw new RuntimeException('The upload folder could not be created.');
    }

    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('The uploaded file could not be saved.');
    }

    return [
        'original_name' => (string) $file['name'],
        'file_name' => $storedName,
        'file_path' => $relativeDir . '/' . $storedName,
        'file_size' => human_file_size((int) $file['size']),
        'file_type' => strtoupper($extension),
    ];
}

function paginate(array $items, int $page, int $perPage): array
{
    $total = count($items);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    $offset = ($page - 1) * $perPage;

    return [
        'items' => array_slice($items, $offset, $perPage),
        'page' => $page,
        'pages' => $pages,
        'total' => $total,
    ];
}

function build_query(array $overrides): string
{
    $query = array_merge($_GET, $overrides);
    return http_build_query(array_filter($query, static fn ($value) => $value !== '' && $value !== null));
}

function id_from_post(string $key): ?int
{
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);

    return $value === false ? null : $value;
}
