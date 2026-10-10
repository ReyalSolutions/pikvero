<?php
// Keep the clean bookings URL on the management page, including its filters.
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../bookings.php' . ($query !== '' ? '?' . $query : ''), true, 302);
exit;
