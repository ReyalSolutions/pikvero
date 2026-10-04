<?php
/**
 * Pikvero Main Entry Router
 */
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$isSubfolder = (strpos($requestUri, '/pikvero') === 0);
$target = $isSubfolder ? '/pikvero/public/index.php' : '/public/index.php';

header("Location: {$target}");
exit;

