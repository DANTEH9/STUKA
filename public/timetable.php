<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_login();

$repo = repository();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_academic_manager();
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('timetable.php');
    }

    try {
        $repo->createTimetable([
            'course_id' => post_value('course_id'),
            'module_id' => post_value('module_id'),
            'lecturer_id' => post_value('lecturer_id'),
            'semester_id' => post_value('semester_id'),
            'day_of_week' => post_value('day_of_week'),
            'start_time' => post_value('start_time'),
            'end_time' => post_value('end_time'),
            'room' => post_value('room'),
            'class_group' => post_value('class_group'),
        ], $user);
        set_flash('success', 'Timetable slot created.');
    } catch (Throwable $error) {
        set_flash('error', $error->getMessage());
    }

    redirect('timetable.php');
}

$courses = $repo->getCourseOptions();
$modules = $repo->getModuleOptions();
$lecturers = $repo->getLecturerOptions();
$semesters = $repo->getSemesterOptions();
$filters = [
    'course_id' => request_value('course_id'),
    'day' => request_value('day'),
    'search' => request_value('search'),
];
$timetable = $repo->getTimetable($filters, $user);
$pagination = paginate($timetable, (int) request_value('page', '1'), app_config('items_per_page'));

page_start('Timetable', 'timetable');
?>
<section class="page-title-row">
    <div>
        <h2>Timetable</h2>
        <p>Create teaching slots and show students only the timetable connected to their courses.</p>
    </div>
</section>

<?php if (can_manage_academics($user['role'])): ?>
<section class="panel compact">
    <div class="panel-heading"><h2>Add timetable slot</h2></div>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>
            <span>Course</span>
            <select name="course_id" required>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course['id']) ?>"><?= e($course['code']) ?> - <?= e($course['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Module</span>
            <select name="module_id">
                <option value="">Whole course</option>
                <?php foreach ($modules as $module): ?>
                    <option value="<?= e($module['id']) ?>"><?= e($module['code']) ?> - <?= e($module['title'] ?? $module['module_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Lecturer</span>
            <select name="lecturer_id">
                <option value="">Unassigned</option>
                <?php foreach ($lecturers as $lecturer): ?>
                    <option value="<?= e($lecturer['id']) ?>"><?= e($lecturer['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Semester</span>
            <select name="semester_id">
                <?php foreach ($semesters as $semester): ?>
                    <option value="<?= e($semester['id']) ?>"><?= e($semester['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Day</span>
            <select name="day_of_week" required>
                <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day): ?>
                    <option value="<?= e($day) ?>"><?= e($day) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><span>Start</span><input type="time" name="start_time" required></label>
        <label><span>End</span><input type="time" name="end_time" required></label>
        <label><span>Room</span><input name="room" required></label>
        <label><span>Class group</span><input name="class_group"></label>
        <button class="button primary" type="submit">Create slot</button>
    </form>
</section>
<?php endif; ?>

<section class="page-toolbar">
    <form method="get" class="filter-bar">
        <select name="course_id" aria-label="Course">
            <option value="">All courses</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?= e($course['id']) ?>" <?= selected($filters['course_id'], (string) $course['id']) ?>><?= e($course['code']) ?> - <?= e($course['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="day" aria-label="Day">
            <option value="">All days</option>
            <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day): ?>
                <option value="<?= e($day) ?>" <?= selected($filters['day'], $day) ?>><?= e($day) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Search timetable">
        <button class="button yellow" type="submit">Search</button>
    </form>
</section>

<section class="panel table-panel">
    <div class="table-actions"><span><?= e($pagination['total']) ?> classes</span></div>
    <div class="responsive-table">
        <table>
            <thead><tr><th>Day</th><th>Time</th><th>Course</th><th>Module</th><th>Lecturer</th><th>Room</th></tr></thead>
            <tbody>
                <?php foreach ($pagination['items'] as $slot): ?>
                    <tr>
                        <td><strong><?= e($slot['day']) ?></strong></td>
                        <td><?= e($slot['time']) ?></td>
                        <td><strong><?= e($slot['course_title']) ?></strong><span><?= e($slot['course_code']) ?></span></td>
                        <td><?= e($slot['module_name'] ?: 'Whole course') ?></td>
                        <td><?= e($slot['lecturer'] ?? 'Unassigned') ?></td>
                        <td><span class="pill soft"><?= e($slot['room']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
pagination_controls($pagination);
page_end();
