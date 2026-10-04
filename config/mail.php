<?php
/**
 * Mail / SMTP Configuration Settings for Pikvero
 */

// If a local or server override exists, prioritize it
$localConfigFile = __DIR__ . '/mail.local.php';
if (file_exists($localConfigFile)) {
    return include $localConfigFile;
}

return [
    'driver'       => getenv('MAIL_DRIVER') ?: 'smtp', // 'smtp' or 'mail'
    'host'         => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'port'         => (int)(getenv('MAIL_PORT') ?: 587),
    'encryption'   => getenv('MAIL_ENCRYPTION') ?: 'tls',
    'username'     => getenv('MAIL_USERNAME') ?: '',
    'password'     => getenv('MAIL_PASSWORD') ?: '',
    'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@pikvero.com',
    'from_name'    => getenv('MAIL_FROM_NAME') ?: 'Pikvero Courts Notification System',
];

