<?php
namespace App\Application\Services;

use App\Infrastructure\Repositories\CourtRepository;
use App\Infrastructure\Repositories\BookingRepository;
use App\Core\Database\Connection;
use Exception;

class AvailabilityEngine {
    private CourtRepository $courtRepo;
    private BookingRepository $bookingRepo;

    public function __construct() {
        $this->courtRepo = new CourtRepository();
        $this->bookingRepo = new BookingRepository();
    }

    public function getAvailableTimeSlots(int $courtId, string $date): array {
        $court = $this->courtRepo->findById($courtId);
        if (!$court || $court['status'] !== 'active') {
            return [];
        }

        $dayOfWeek = (int)date('w', strtotime($date));
        // 0=Sun,1=Mon,...,6=Sat → determine day_type
        $isWeekend  = in_array($dayOfWeek, [0, 6]);
        $dayType    = $isWeekend ? 'weekend' : 'weekday';

        $db = Connection::getInstance();
        $opHours = $db->selectOne("SELECT open_time, close_time FROM court_operating_hours WHERE court_id = ? AND day_of_week = ?", [$courtId, $dayOfWeek], 'ii');

        $openTime  = $opHours ? $opHours['open_time']  : '06:00:00';
        $closeTime = $opHours ? $opHours['close_time'] : '22:00:00';

        $startHour = (int)date('H', strtotime($openTime));
        $endHour   = (int)date('H', strtotime($closeTime));

        // Load all active pricing rules for this court, ordered by specificity
        $pricingRules = $db->select(
            "SELECT * FROM court_pricing
              WHERE court_id = ?
                AND is_active = 1
              ORDER BY
                CASE WHEN day_of_week IS NOT NULL THEN 0 ELSE 1 END ASC,
                start_time ASC",
            [$courtId], 'i'
        );

        $activeBookings    = $this->bookingRepo->getActiveBookingsForCourtDate($courtId, $date);
        $blockedSchedules  = $this->courtRepo->getBlockedSchedules($courtId, $date);
        $openPlaySessions = $db->select(
            "SELECT start_time, end_time FROM open_play_sessions WHERE court_id = ? AND session_date = ? AND status IN ('open', 'full')",
            [$courtId, $date], 'is'
        );

        $slots = [];
        for ($h = $startHour; $h < $endHour; $h++) {
            $slotStart = sprintf('%02d:00:00', $h);
            $slotEnd   = sprintf('%02d:00:00', $h + 1);

            // --- Determine price for this slot ---
            $slotPrice = (float)$court['base_price_per_hour'];
            foreach ($pricingRules as $rule) {
                // Time range must overlap the slot
                if ($rule['start_time'] >= $slotEnd || $rule['end_time'] <= $slotStart) {
                    continue;
                }
                // Day-of-week filter (NULL means all days)
                if ($rule['day_of_week'] !== null && (int)$rule['day_of_week'] !== $dayOfWeek) {
                    continue;
                }
                // Day-type filter ('all', 'weekday', 'weekend')
                $ruleType = strtolower($rule['day_type'] ?? 'all');
                if ($ruleType !== 'all' && $ruleType !== $dayType) {
                    continue;
                }
                // First matching rule wins (already ordered by specificity)
                $slotPrice = (float)$rule['price_per_hour'];
                break;
            }

            // --- Availability checks ---
            $isBooked = false;
            foreach ($activeBookings as $b) {
                if ($slotStart < $b['end_time'] && $slotEnd > $b['start_time']) {
                    $isBooked = true;
                    break;
                }
            }

            $isBlocked   = false;
            $blockReason = '';
            foreach ($blockedSchedules as $blk) {
                if ($slotStart < $blk['end_time'] && $slotEnd > $blk['start_time']) {
                    $isBlocked   = true;
                    $blockReason = $blk['reason'];
                    break;
                }
            }

            $isOpenPlay = false;
            foreach ($openPlaySessions as $session) {
                if ($slotStart < $session['end_time'] && $slotEnd > $session['start_time']) {
                    $isOpenPlay = true;
                    break;
                }
            }
            $available = !$isBooked && !$isBlocked && !$isOpenPlay;

            $slots[] = [
                'start_time'    => sprintf('%02d:00', $h),
                'end_time'      => sprintf('%02d:00', $h + 1),
                'display_start' => $this->to12h($h),
                'display_end'   => $this->to12h($h + 1),
                'formatted'     => $this->to12h($h) . ' - ' . $this->to12h($h + 1),
                'price'         => $slotPrice,
                'available'     => $available,
                'status'        => $isOpenPlay ? 'open_play' : ($isBooked ? 'booked' : ($isBlocked ? 'blocked' : 'available')),
                'reason'        => $isOpenPlay ? 'Closed for Open Play' : $blockReason,
            ];
        }

        return $slots;
    }

    public function validateSlotAvailability(int $courtId, string $date, string $startTime, string $endTime): void {
        $court = $this->courtRepo->findById($courtId);
        if (!$court) {
            throw new Exception("Selected court does not exist.");
        }
        if ($court['status'] !== 'active') {
            throw new Exception("Selected court is currently unavailable or under maintenance.");
        }

        $openPlay = Connection::getInstance()->selectOne(
            "SELECT id FROM open_play_sessions WHERE court_id = ? AND session_date = ? AND status IN ('open', 'full') AND start_time < ? AND end_time > ? LIMIT 1",
            [$courtId, $date, $endTime, $startTime], 'isss'
        );
        if ($openPlay) {
            throw new Exception("This court is closed for Open Play during the selected time. Please select another slot.");
        }

        // Prevent Double Booking
        if ($this->bookingRepo->checkOverlapping($courtId, $date, $startTime, $endTime)) {
            throw new Exception("This time slot is no longer available. Please select another slot.");
        }
    }

    /** Convert a 24-hour integer hour to 12-hour AM/PM display string. */
    private function to12h(int $h): string {
        $h = $h % 24;
        $suffix = $h < 12 ? 'AM' : 'PM';
        $h12 = $h % 12;
        if ($h12 === 0) $h12 = 12;
        return $h12 . ':00 ' . $suffix;
    }
}
