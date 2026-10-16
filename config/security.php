<?php

return [
    'backup_age_recipient' => env('BACKUP_AGE_RECIPIENT'),
    'absolute_session_minutes' => (int) env('SECURITY_SESSION_MAX_MINUTES', 480),
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', ''))))),
    'trusted_hosts' => array_values(array_filter(array_map('trim', explode(',', env('TRUSTED_HOSTS', ''))))),
];
