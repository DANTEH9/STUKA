<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);
define('PUBLIC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'public');

require APP_PATH . '/includes/functions.php';
require APP_PATH . '/models/DemoRepository.php';
require APP_PATH . '/models/DatabaseRepository.php';

load_env_file(ROOT_PATH . DIRECTORY_SEPARATOR . '.env');

$appConfig = require APP_PATH . '/config/config.php';

date_default_timezone_set($appConfig['default_timezone']);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($appConfig['session_name']);
    session_start();
}

require APP_PATH . '/includes/auth.php';
require APP_PATH . '/includes/layout.php';

function app_config(?string $key = null): mixed
{
    global $appConfig;

    if ($key === null) {
        return $appConfig;
    }

    return $appConfig[$key] ?? null;
}

function repository(): object
{
    static $repository = null;

    if ($repository !== null) {
        return $repository;
    }

    $dbConfig = require APP_PATH . '/config/database.php';

    try {
        $repository = DatabaseRepository::connect($dbConfig);
    } catch (Throwable $error) {
        if (app_config('allow_demo_fallback')) {
            $_SESSION['database_notice'] = 'Using demo data because MySQL is not connected.';
            $repository = new DemoRepository(require APP_PATH . '/data/demo.php');

            return $repository;
        }

        throw new RuntimeException(
            'STUCA database connection failed. Confirm XAMPP MySQL is running and the .env database settings point to the stuca database. Details: ' . $error->getMessage(),
            0,
            $error
        );
    }

    return $repository;
}
