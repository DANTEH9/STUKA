<?php

declare(strict_types=1);

return [
    'host' => getenv('STUCA_DB_HOST') ?: getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('STUCA_DB_PORT') ?: getenv('DB_PORT') ?: '3306',
    'database' => getenv('STUCA_DB_NAME') ?: getenv('DB_DATABASE') ?: 'stuca',
    'username' => getenv('STUCA_DB_USER') ?: getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('STUCA_DB_PASS') ?: getenv('DB_PASSWORD') ?: '',
    'charset' => getenv('STUCA_DB_CHARSET') ?: 'utf8mb4',
];
