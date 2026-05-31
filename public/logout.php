<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

logout_user();
redirect('login.php');
