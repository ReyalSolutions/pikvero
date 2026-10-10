<?php
require_once __DIR__ . '/../../app/bootstrap.php';
if (!\App\Core\Auth\Auth::check()) {
    header('Location: /pikvero/public/login.php');
    exit;
}
$playerSearch = true;
require __DIR__ . '/../search.php';
