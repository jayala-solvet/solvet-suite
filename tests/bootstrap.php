<?php

// Synchronize PHPUnit's forced variables for runtimes that also expose process
// environment through $_SERVER. Tests must never connect to an installed DB.
foreach (['APP_ENV','APP_MAINTENANCE_DRIVER','BCRYPT_ROUNDS','BROADCAST_CONNECTION','CACHE_STORE','DB_CONNECTION','DB_DATABASE','DB_URL','MAIL_MAILER','QUEUE_CONNECTION','SESSION_DRIVER'] as $key) {
    if (array_key_exists($key, $_ENV)) {
        $_SERVER[$key] = $_ENV[$key];
        putenv($key.'='.$_ENV[$key]);
    }
}
require dirname(__DIR__).'/vendor/autoload.php';
