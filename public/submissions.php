<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require_login();

http_response_code(410);
page_start('Feature removed', 'dashboard');
empty_state('Feature removed', 'This coursework workflow has been removed from STUCA.', 'dashboard.php', 'Back to dashboard');
page_end();
