<?php
namespace App\Application\Services;

use App\Core\Database\Connection;

class CourtPricing {
    /** Price each interval separately, including rule boundaries within an hour. */
    public function quote(int $courtId, string $date, string $start, string $end, float $baseRate): float {
        $rules = Connection::getInstance()->select(
            'SELECT * FROM court_pricing WHERE court_id = ? AND is_active = 1 ORDER BY (day_of_week IS NOT NULL) DESC, start_time ASC, id ASC',
            [$courtId], 'i'
        );
        return self::calculate($date, $start, $end, $baseRate, $rules);
    }

    public static function calculate(string $date, string $start, string $end, float $baseRate, array $rules): float {
        $seconds = static function(string $time): int {
            $parts = array_map('intval', explode(':', $time));
            return $parts[0] * 3600 + ($parts[1] ?? 0) * 60 + ($parts[2] ?? 0);
        };
        $from = $seconds($start); $to = $seconds($end);
        if ($to <= $from) throw new \InvalidArgumentException('Invalid pricing time range.');
        $day = (int)date('w', strtotime($date));
        $dayName = strtolower(date('l', strtotime($date)));
        $dayType = in_array($day, [0, 6], true) ? 'weekend' : 'weekday';
        $matching = [];
        $boundaries = [$from, $to];
        foreach ($rules as $rule) {
            if (isset($rule['is_active']) && !(int)$rule['is_active']) continue;
            if ($rule['day_of_week'] !== null && (int)$rule['day_of_week'] !== $day) continue;
            $types = array_map('trim', explode(',', strtolower($rule['day_type'] ?: 'all')));
            if (!array_intersect($types, ['all', $dayType, $dayName])) continue;
            $a = max($from, $seconds($rule['start_time']));
            $b = min($to, $seconds($rule['end_time']));
            if ($a >= $b) continue;
            $matching[] = [$a, $b, (float)$rule['price_per_hour']];
            $boundaries[] = $a; $boundaries[] = $b;
        }
        sort($boundaries, SORT_NUMERIC);
        $boundaries = array_values(array_unique($boundaries));
        $amount = 0;
        for ($i = 1; $i < count($boundaries); $i++) {
            $a = $boundaries[$i-1]; $b = $boundaries[$i]; $rate = $baseRate;
            foreach ($matching as [$ruleStart, $ruleEnd, $ruleRate]) {
                if ($ruleStart <= $a && $ruleEnd >= $b) { $rate = $ruleRate; break; }
            }
            $amount += ($b - $a) / 3600 * $rate;
        }
        return round($amount, 2);
    }
}
