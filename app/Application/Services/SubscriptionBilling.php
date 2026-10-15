<?php
namespace App\Application\Services;

use DateTimeImmutable;
use InvalidArgumentException;

final class SubscriptionBilling {
    public static function periodEnd(?string $value): ?DateTimeImmutable {
        if (!$value) return null;
        try {
            $date = new DateTimeImmutable($value);
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $date->setTime(23, 59, 59) : $date;
        } catch (\Throwable $e) { return null; }
    }

    public static function isActive(?array $subscription, ?DateTimeImmutable $now = null): bool {
        if (!$subscription || !in_array(strtolower($subscription['status'] ?? ''), ['active', 'trial', 'trialing'], true)) return false;
        $end = self::periodEnd($subscription['current_period_end'] ?? null);
        return $end !== null && $end >= ($now ?? new DateTimeImmutable());
    }

    public static function renewalEnd(?array $subscription, int $months, ?DateTimeImmutable $now = null): string {
        $now = $now ?? new DateTimeImmutable();
        $base = self::isActive($subscription, $now) ? self::periodEnd($subscription['current_period_end']) : $now;
        // Clamp month-end renewals (January 31 -> February 28/29), rather than skipping a month.
        $target = $base->modify('first day of this month')->modify('+' . $months . ' months');
        $day = min((int)$base->format('j'), (int)$target->format('t'));
        return $target->setDate((int)$target->format('Y'), (int)$target->format('n'), $day)->format('Y-m-d');
    }

    public static function quote(array $plan, string $cycle, bool $usedTrial): array {
        if (!in_array($cycle, ['monthly', 'yearly'], true)) throw new InvalidArgumentException('Invalid billing cycle.');
        $trial = (int)($plan['is_free_trial'] ?? 0) === 1 && !$usedTrial;
        $monthly = (float)($plan['monthly_price'] ?? 0);
        $yearly = (float)($plan['yearly_price'] ?? 0);
        if ($yearly <= 0) $yearly = round($monthly * 12 * .70, 2);
        $base = $trial ? 0 : ($cycle === 'yearly' ? $yearly : $monthly);
        if ($usedTrial && (int)($plan['is_free_trial'] ?? 0) === 1 && $base <= 0) {
            throw new InvalidArgumentException('The free trial has already been used. Select a paid plan.');
        }
        $platform = round($base * .02, 2);
        $gateway = round($base * .025, 2);
        $total = round($base + $platform + $gateway, 2);
        return ['trial_eligible' => $trial, 'base' => $base, 'platform_fee' => $platform, 'gateway_fee' => $gateway, 'total' => $total, 'amount_centavos' => (int)round($total * 100)];
    }

    public static function validateCycle(?array $subscription, string $cycle): void {
        if (self::isActive($subscription) && in_array($subscription['billing_cycle'] ?? '', ['annual', 'yearly'], true) && $cycle === 'monthly') {
            throw new InvalidArgumentException('Monthly billing becomes available when your current annual term ends.');
        }
    }
}
