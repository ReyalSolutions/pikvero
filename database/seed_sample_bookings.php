<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;

$db = Connection::getInstance();
echo "Seeding sample court bookings for calendar...\n";

$courts = $db->select("SELECT id, facility_id, organization_id FROM courts LIMIT 2");
if (!empty($courts)) {
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    foreach ($courts as $index => $c) {
        $courtId = (int)$c['id'];
        $facId = (int)$c['facility_id'];
        $orgId = (int)($c['organization_id'] ?? 1);
        $ref = 'BK-CAL-' . rand(1000, 9999);

        $db->execute("
            INSERT INTO bookings (booking_reference, customer_id, court_id, facility_id, organization_id, booking_date, start_time, end_time, duration_hours, rate_per_hour, total_amount, payment_status, booking_status)
            VALUES (?, 1, ?, ?, ?, ?, ?, ?, 2, 350.00, 700.00, 'paid', 'confirmed')
        ", [
            $ref,
            $courtId,
            $facId,
            $orgId,
            $index === 0 ? $today : $tomorrow,
            $index === 0 ? '09:00:00' : '14:00:00',
            $index === 0 ? '11:00:00' : '16:00:00'
        ], 'siiisss');
    }
    echo "Sample court bookings seeded for today and tomorrow.\n";
} else {
    echo "No courts found to seed bookings.\n";
}
