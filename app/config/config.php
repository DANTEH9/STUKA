<?php

declare(strict_types=1);

return [
    'app_name' => getenv('STUCA_APP_NAME') ?: 'STUCA',
    'app_subtitle' => 'Student Course Registration and Academic Portal',
    'session_name' => getenv('STUCA_SESSION_NAME') ?: 'stuca_session',
    'items_per_page' => (int) (getenv('STUCA_ITEMS_PER_PAGE') ?: 10),
    'upload_max_bytes' => (int) (getenv('STUCA_UPLOAD_MAX_BYTES') ?: 10 * 1024 * 1024),
    'allowed_upload_extensions' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'jpg', 'jpeg', 'png'],
    'default_timezone' => getenv('STUCA_TIMEZONE') ?: 'Africa/Nairobi',
    'allow_demo_fallback' => filter_var(getenv('STUCA_ALLOW_DEMO_FALLBACK') ?: 'false', FILTER_VALIDATE_BOOLEAN),
];
