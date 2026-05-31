<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_role(['super_admin', 'admin', 'lecturer']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Your session expired. Please try again.');
    redirect('materials.php');
}

try {
    $upload = safe_uploaded_file('material_file', 'materials');
    repository()->createMaterial([
        'course_id' => post_value('course_id'),
        'module_id' => post_value('module_id'),
        'title' => post_value('title') ?: $upload['original_name'],
        'file_name' => $upload['original_name'],
        'file_path' => $upload['file_path'],
        'file_size' => $upload['file_size'],
        'file_type' => $upload['file_type'],
        'visibility' => post_value('visibility', 'course'),
    ], current_user());
    set_flash('success', 'Study material uploaded.');
} catch (Throwable $error) {
    set_flash('error', $error->getMessage());
}

redirect('materials.php');
