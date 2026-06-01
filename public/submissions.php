<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_role(['super_admin', 'admin', 'department_head', 'lecturer']);

$repo = repository();
$user = current_user();
$assignmentId = (int) request_value('assignment_id', '0');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('assignments.php');
    }

    try {
        $submission = $repo->getSubmissionForReview((int) post_value('submission_id'), $user);
        if (!$submission) {
            throw new RuntimeException('Submission was not found or is outside your teaching scope.');
        }

        $repo->markSubmissionReviewed((int) $submission['id'], $user, post_value('review_remarks'));
        set_flash('success', 'Submission marked as reviewed.');
        redirect('submissions.php?assignment_id=' . (int) $submission['assignment_id']);
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
        redirect($assignmentId > 0 ? 'submissions.php?assignment_id=' . $assignmentId : 'assignments.php');
    }
}

$assignment = $assignmentId > 0 ? $repo->getAssignmentById($assignmentId, $user) : null;

if (!$assignment) {
    http_response_code(404);
    page_start('Submissions', 'assignments');
    empty_state('Assignment not found', 'Choose an assignment from the Assignments page to review its submissions.', 'assignments.php', 'Back to assignments');
    page_end();
    exit;
}

$submissions = $repo->getAssignmentSubmissions($assignmentId);
$submittedCount = count(array_filter($submissions, static fn (array $row): bool => !empty($row['submission_id'])));
$reviewedCount = count(array_filter($submissions, static fn (array $row): bool => !empty($row['reviewed_at'])));
$lateCount = count(array_filter($submissions, static fn (array $row): bool => (int) ($row['is_late'] ?? 0) === 1));
$missingCount = count(array_filter($submissions, static fn (array $row): bool => ($row['workflow_status'] ?? '') === 'Missing'));
$notReviewedCount = max(0, $submittedCount - $reviewedCount);

page_start('Submissions', 'assignments');
?>
<section class="page-title-row">
    <div>
        <h2><?= e($assignment['title']) ?></h2>
        <p><?= e($assignment['course_code']) ?> - <?= e($assignment['module_name'] ?: $assignment['course_title']) ?> - Due <?= e(display_date($assignment['deadline'])) ?></p>
    </div>
    <div class="hero-actions">
        <a class="button quiet" href="assignments.php">Back to assignments</a>
    </div>
</section>

<section class="panel compact">
    <div class="review-summary">
        <span class="pill success"><?= e((string) $reviewedCount) ?> Reviewed</span>
        <span class="pill warning"><?= e((string) $notReviewedCount) ?> Not Reviewed</span>
        <span class="pill danger"><?= e((string) $lateCount) ?> Late</span>
        <span class="pill soft"><?= e((string) $missingCount) ?> Missing</span>
    </div>
</section>

<section class="panel table-panel">
    <div class="table-actions">
        <span><?= e((string) count($submissions)) ?> expected submissions</span>
    </div>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Submitted File</th>
                    <th>Submission Time</th>
                    <th>Submission Status</th>
                    <th>Late Status</th>
                    <th>Review</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission): ?>
                    <?php
                    $workflowStatus = $submission['workflow_status'] ?? 'Not Submitted';
                    $statusClass = match ($workflowStatus) {
                        'Reviewed' => 'success',
                        'Late Submission', 'Missing' => 'danger',
                        'Submitted' => 'soft',
                        default => 'warning',
                    };
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($submission['student_name']) ?></strong>
                            <span><?= e($submission['student_number'] ?: $submission['student_email']) ?></span>
                        </td>
                        <td>
                            <?php if (!empty($submission['submission_id'])): ?>
                                <strong><?= e($submission['original_name']) ?></strong>
                                <span><?= e($submission['student_comment'] ?: 'No comment') ?></span>
                            <?php else: ?>
                                <span class="muted-text">No file submitted</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(!empty($submission['submitted_at']) ? display_datetime($submission['submitted_at']) : 'Not submitted') ?></td>
                        <td><span class="pill <?= e($statusClass) ?>"><?= e($workflowStatus) ?></span></td>
                        <td>
                            <?php if ((int) ($submission['is_late'] ?? 0) === 1): ?>
                                <span class="pill danger"><?= e(late_duration_label((int) $submission['late_duration_minutes'])) ?></span>
                            <?php else: ?>
                                <span class="pill soft"><?= !empty($submission['submission_id']) ? 'On time' : 'Pending' ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($submission['reviewed_at'])): ?>
                                <strong>&#10003; Reviewed</strong>
                                <span><?= e(display_datetime($submission['reviewed_at'])) ?></span>
                                <small><?= e($submission['review_remarks'] ?: 'No remarks') ?></small>
                            <?php else: ?>
                                <span class="muted-text"><?= !empty($submission['submission_id']) ? 'Not reviewed' : 'No submission' ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="action-cell">
                            <?php if (!empty($submission['submission_id'])): ?>
                                <a class="button quiet" href="download-submission.php?id=<?= e($submission['submission_id']) ?>">Open file</a>
                                <form method="post" class="inline-upload">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="submission_id" value="<?= e($submission['submission_id']) ?>">
                                    <input name="review_remarks" value="<?= e($submission['review_remarks'] ?? '') ?>" placeholder="Remarks">
                                    <button class="button primary" type="submit">Save review</button>
                                </form>
                            <?php else: ?>
                                <span class="muted-text">Missing submission</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
page_end();
