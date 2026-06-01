<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_academic_manager();

$reports = repository()->getReports();

page_start('Reports', 'reports');
?>
<section class="page-title-row">
    <div>
        <h2>Reports</h2>
        <p>Quick operational summaries for registrations, teaching load, materials, and results.</p>
    </div>
</section>

<section class="report-grid">
    <?php foreach ($reports as $key => $rows): ?>
        <article class="panel">
            <div class="panel-heading">
                <h2><?= e(ucwords(str_replace('_', ' ', $key))) ?></h2>
            </div>
            <div class="report-list">
                <?php foreach ($rows as $row): ?>
                    <div class="report-row">
                        <span>
                            <strong><?= e($row['title'] ?? $row['status'] ?? $row['grade'] ?? 'Record') ?></strong>
                            <small><?= e($row['code'] ?? ($row['average_score'] ?? '')) ?></small>
                        </span>
                        <b><?= e($row['total'] ?? 0) ?></b>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<?php
page_end();
