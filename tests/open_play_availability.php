<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database\Connection;
use App\Application\Services\AvailabilityEngine;
use App\Infrastructure\Repositories\OpenPlayRepository;

function expect(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function expectRejected(callable $action, string $message): void {
    try { $action(); } catch (Exception $e) { return; }
    throw new RuntimeException($message);
}

$db = Connection::getInstance();
$db->beginTransaction();
try {
    $court = $db->selectOne("SELECT id, facility_id FROM courts WHERE status = 'active' ORDER BY id LIMIT 1");
    expect((bool)$court, 'An active court is required for this integration check.');
    $courtId = (int)$court['id'];
    $date = '2099-01-05';
    $engine = new AvailabilityEngine();
    $repo = new OpenPlayRepository();
    $baseline = $engine->getAvailableTimeSlots($courtId, $date);
    expect(count($baseline) >= 4 && $baseline[0]['available'] && $baseline[3]['available'], 'Test date requires four free operating slots.');
    $start = substr($baseline[0]['start_time'], 0, 2) . ':30:00';
    $end = substr($baseline[2]['start_time'], 0, 2) . ':30:00';
    $data = ['facility_id' => $court['facility_id'], 'court_id' => $courtId, 'title' => 'Availability integration check', 'session_date' => $date, 'start_time' => $start, 'end_time' => $end, 'status' => 'open'];
    $id = $repo->createSession($data);
    expect((int)$repo->getSessionById($id)['court_id'] === $courtId, 'Court assignment was not saved.');
    $slots = $engine->getAvailableTimeSlots($courtId, $date);
    foreach ([0, 1, 2] as $index) {
        expect(!$slots[$index]['available'] && $slots[$index]['status'] === 'open_play' && $slots[$index]['reason'] === 'Closed for Open Play', 'Partial and full overlapping hours must be closed and identified as Open Play.');
    }
    expect($slots[3]['available'], 'Hours outside the session must stay available.');
    expectRejected(fn() => $engine->validateSlotAvailability($courtId, $date, $baseline[0]['start_time'], $baseline[1]['start_time']), 'Booking submission must reject partial overlaps.');
    $engine->validateSlotAvailability($courtId, $date, $baseline[0]['start_time'] . ':00', $start);
    $engine->validateSlotAvailability($courtId, $date, $end, $baseline[3]['end_time'] . ':00');
    expectRejected(fn() => $repo->createSession($data), 'Overlapping sessions must be rejected.');
    $data['status'] = 'full';
    $repo->updateSession($id, $data);
    expect(!$engine->getAvailableTimeSlots($courtId, $date)[1]['available'], 'Full sessions must also block bookings.');
    $other = $db->selectOne("SELECT id FROM courts WHERE status = 'active' AND id != ? LIMIT 1", [$courtId], 'i');
    if ($other) {
        $otherSlots = $engine->getAvailableTimeSlots((int)$other['id'], $date);
        expect(!in_array('Closed for Open Play', array_column($otherSlots, 'reason'), true), 'Other courts must remain unaffected.');
    }
    foreach (['cancelled', 'completed'] as $status) {
        $data['status'] = $status;
        $repo->updateSession($id, $data);
        expect($engine->getAvailableTimeSlots($courtId, $date)[1]['available'], 'Inactive sessions must release booking hours.');
    }
    $data['court_id'] = 0;
    expectRejected(fn() => $repo->createSession($data), 'Missing courts must be rejected.');
    $data['court_id'] = $courtId;
    $data['facility_id'] = 0;
    expectRejected(fn() => $repo->createSession($data), 'Courts from a different facility must be rejected.');
    echo "PASS: court persistence, partial overlaps, boundaries, server rejection, full/cancelled/completed status, and court isolation.\n";
} finally {
    $db->rollback();
}
