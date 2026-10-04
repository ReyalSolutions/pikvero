<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;

$db = Connection::getInstance();
echo "Seeding active Open Play sessions for current dates...\n";

$facilities = $db->select("SELECT id FROM facilities LIMIT 3");
if (!empty($facilities)) {
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $nextWeek = date('Y-m-d', strtotime('+3 days'));

    $sessionsToSeed = [
        ['title' => 'Friday Night Social Doubles', 'date' => $today, 'start' => '18:00:00', 'end' => '21:00:00', 'fee' => 70.00, 'max' => 16],
        ['title' => 'Saturday Morning Beginner Open Play', 'date' => $tomorrow, 'start' => '07:00:00', 'end' => '10:00:00', 'fee' => 70.00, 'max' => 12],
        ['title' => 'Midweek All-Levels King of the Court', 'date' => $nextWeek, 'start' => '17:00:00', 'end' => '20:00:00', 'fee' => 80.00, 'max' => 20]
    ];

    foreach ($sessionsToSeed as $index => $sess) {
        $facId = (int)$facilities[$index % count($facilities)]['id'];

        $db->execute("
            INSERT INTO open_play_sessions (facility_id, title, session_date, start_time, end_time, fee_per_player, max_players, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'open')
        ", [
            $facId,
            $sess['title'],
            $sess['date'],
            $sess['start'],
            $sess['end'],
            $sess['fee'],
            $sess['max']
        ], 'issssdi');
    }
    echo "Active Open Play sessions seeded successfully.\n";
} else {
    echo "No facilities found to seed Open Play sessions.\n";
}
