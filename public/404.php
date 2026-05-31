<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
http_response_code(404);

page_start('Page not found', 'not-found');
?>
<section class="empty-state">
    <strong>404</strong>
    <h2>Page not found</h2>
    <p>The page may have moved, or the link may be incomplete.</p>
    <a class="button primary" href="dashboard.php">Back to dashboard</a>
</section>
<?php
page_end();
