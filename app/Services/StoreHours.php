<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Opening hours for online ordering, read from Website settings → Timings.
 * A closing time earlier than the opening time means "open past midnight".
 */
class StoreHours
{
    public const DAYS = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];

    public const DEFAULT_OPEN = '11:00';

    public const DEFAULT_CLOSE = '23:30';

    /**
     * @return array{open: bool, message: ?string, opens_at: ?string}
     */
    public function status(?Carbon $at = null): array
    {
        $at ??= now();

        if (! setting('ordering_enabled', true)) {
            return ['open' => false, 'message' => 'Online ordering is closed right now. Please call us to order.', 'opens_at' => null];
        }

        if (! setting('enforce_hours', true)) {
            return ['open' => true, 'message' => null, 'opens_at' => null];
        }

        foreach ([$at->copy()->subDay(), $at->copy()] as $day) {
            $window = $this->windowFor($day);

            if ($window && $at->betweenIncluded($window[0], $window[1])) {
                return ['open' => true, 'message' => null, 'opens_at' => null];
            }
        }

        $next = $this->nextOpening($at);

        return [
            'open' => false,
            'message' => $next
                ? 'We are closed right now. Ordering opens '.$this->describe($next, $at).'.'
                : 'Online ordering is closed right now.',
            'opens_at' => $next?->toIso8601String(),
        ];
    }

    public function isOpen(?Carbon $at = null): bool
    {
        return $this->status($at)['open'];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}|null
     */
    protected function windowFor(Carbon $day): ?array
    {
        $key = strtolower($day->format('D'));

        if (setting("hours_{$key}_closed", false)) {
            return null;
        }

        $open = $this->timeOn($day, (string) setting("hours_{$key}_open", self::DEFAULT_OPEN));
        $close = $this->timeOn($day, (string) setting("hours_{$key}_close", self::DEFAULT_CLOSE));

        if (! $open || ! $close) {
            return null;
        }

        if ($close->lessThanOrEqualTo($open)) {
            $close->addDay();
        }

        return [$open, $close->subSecond()];
    }

    protected function timeOn(Carbon $day, string $time): ?Carbon
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})/', $time, $parts)) {
            return null;
        }

        return $day->copy()->setTime((int) $parts[1], (int) $parts[2]);
    }

    protected function nextOpening(Carbon $at): ?Carbon
    {
        for ($offset = 0; $offset <= 7; $offset++) {
            $window = $this->windowFor($at->copy()->addDays($offset));

            if ($window && $window[0]->greaterThan($at)) {
                return $window[0];
            }
        }

        return null;
    }

    protected function describe(Carbon $opening, Carbon $at): string
    {
        $time = $opening->format('g:i A');

        return match (true) {
            $opening->isSameDay($at) => 'today at '.$time,
            $opening->isSameDay($at->copy()->addDay()) => 'tomorrow at '.$time,
            default => 'on '.$opening->format('l').' at '.$time,
        };
    }

    /**
     * @return list<array{day: string, hours: string}>
     */
    public function week(): array
    {
        $rows = [];

        foreach (self::DAYS as $key => $label) {
            $rows[] = [
                'day' => $label,
                'hours' => setting("hours_{$key}_closed", false)
                    ? 'Closed'
                    : $this->pretty((string) setting("hours_{$key}_open", self::DEFAULT_OPEN)).' – '.$this->pretty((string) setting("hours_{$key}_close", self::DEFAULT_CLOSE)),
            ];
        }

        return $rows;
    }

    protected function pretty(string $time): string
    {
        return $this->timeOn(today(), $time)?->format('g:i A') ?? $time;
    }
}
