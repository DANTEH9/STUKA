<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_role(['super_admin', 'admin', 'department_head', 'lecturer']);

$repo = repository();
$user = current_user();
$submission = $repo->getSubmissionForReview((int) request_value('id', '0'), $user);

if (!$submission || empty($submission['file_path'])) {
    http_response_code(404);
    page_start('Submission not found', 'assignments');
    empty_state('Submission not found', 'The selected submission could not be opened.', 'assignments.php', 'Back to assignments');
    page_end();
    exit;
}

$repo->markSubmissionReviewed((int) $submission['id'], $user);
redirect($submission['file_path']);
