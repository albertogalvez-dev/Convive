<?php

use App\Kernel;

// PHP-FPM does not mirror inherited process variables into $_SERVER and
// $_ENV. Symfony Runtime reads those superglobals before the application is
// constructed, while the immutable production image deliberately contains no
// .env file. Hydrate the reviewed runtime configuration from getenv() first.
foreach ([
    'APP_ENV',
    'APP_DEBUG',
    'APP_SECRET',
    'APP_DEMO_MODE',
    'ATTACHMENT_STORAGE_DIRECTORY',
    'CLAMAV_DSN',
    'DATABASE_URL',
    'DEFAULT_URI',
    'DEMO_PROFESSIONAL_PASSWORD',
    'MAILER_DSN',
    'PUBLIC_REPORTING_MODE',
    'REDIS_DSN',
    'REPORTER_EMAIL_ENABLED',
    'REPORTER_EMAIL_FOLLOW_UP_URL',
    'REPORTER_EMAIL_FROM',
    'REPORTER_EMAIL_PUBLIC_URL',
    'SESSION_DATABASE_URL',
    'TRUSTED_PROXIES',
] as $name) {
    $value = getenv($name);

    if (false !== $value) {
        $_SERVER[$name] ??= $value;
        $_ENV[$name] ??= $value;
    }
}

$_SERVER['APP_RUNTIME_OPTIONS'] ??= $_ENV['APP_RUNTIME_OPTIONS'] ?? '{"disable_dotenv":true}';

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
