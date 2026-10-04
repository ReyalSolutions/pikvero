<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
use App\Core\Auth\Auth;
use App\Infrastructure\Repositories\BookingRepository;

// Session & Authentication Check
if (!Auth::check()) {
    header('Location: /pikvero/public/login.php');
    exit;
}

$user = Auth::user();
$role = $user['role_name'] ?? '';

// Permission check
$canExport = ($role === 'super_admin') || Auth::hasPermission('booking.export', 'audit_logs.export', 'bookings.manage');

if (!$canExport) {
    header('Location: /pikvero/public/403.php?permission=booking.export');
    exit;
}

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? 'all');
$tenantOrgId = in_array($role, ['super_admin', 'platform_admin']) ? 0 : (int)($user['organization_id'] ?? 0);

$bookingRepo = new BookingRepository();
$result = $bookingRepo->getPaginatedBookings($tenantOrgId, 1, 1000, $search, $status);

$records = $result['data'] ?? [];

// Set CSV Download Headers
$filename = 'Pikvero_Reservations_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Output UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Header Row
fputcsv($output, [
    'Booking Reference',
    'Customer Name',
    'Customer Email',
    'Customer Phone',
    'Facility Name',
    'Court Name',
    'Court Type',
    'Surface Type',
    'Booking Date',
    'Start Time',
    'End Time',
    'Duration (Hrs)',
    'Hourly Rate (PHP)',
    'Total Amount (PHP)',
    'Booking Status',
    'Payment Status',
    'Notes / Source',
    'Created At'
]);

// Output Data Rows
foreach ($records as $b) {
    fputcsv($output, [
        $b['booking_reference'],
        $b['customer_name'],
        $b['customer_email'],
        $b['customer_phone'] ?? '',
        $b['facility_name'],
        $b['court_name'],
        strtoupper($b['court_type'] ?? 'indoor'),
        ucwords(str_replace('_', ' ', $b['surface_type'] ?? '')),
        $b['booking_date'],
        $b['start_time'],
        $b['end_time'],
        $b['duration_hours'],
        number_format((float)$b['rate_per_hour'], 2, '.', ''),
        number_format((float)$b['total_amount'], 2, '.', ''),
        strtoupper($b['booking_status']),
        strtoupper($b['payment_status']),
        $b['notes'] ?? '',
        $b['created_at'] ?? ''
    ]);
}

fclose($output);
exit;
