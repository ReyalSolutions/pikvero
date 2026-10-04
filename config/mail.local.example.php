<?php
/**
 * Example Production Mail / SMTP Override
 * 
 * Instructions:
 * 1. Upload this file to InfinityFree inside:
 *    /htdocs/config/mail.local.php
 * 2. This file is git-ignored and will NEVER be overwritten by GitHub Actions deployments.
 */
return [
    'driver'       => 'smtp',
    'host'         => 'smtp.gmail.com',
    'port'         => 587,
    'encryption'   => 'tls',
    'username'     => 'your-email@gmail.com',
    'password'     => 'your-google-app-password',
    'from_address' => 'no-reply@yourdomain.com',
    'from_name'    => 'Pikvero Courts Notification System',
];
