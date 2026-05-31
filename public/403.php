<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
http_response_code(403);

page_start('Access denied', 'forbidden');
?>
<section class="empty-state">
    <strong>403</strong>
    <h2>Access denied</h2>
    <p>Your account does not have permission to open this page.</p>
    <a class="button primary" href="dashboard.php">Back to dashboard</a>
</section>
<?php
page_end();
