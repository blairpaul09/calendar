<?php

namespace Calendar\Builder;

use Calendar\Exceptions\RruleException;
use Carbon\Carbon;
use RRule\RRule;

trait RecurrenceRuleBuilder
{
    /**
     * @var array $rrule
     */
    private array $rrule = [];

    /**
     * @var ?Carbon $startAt
     */
    private ?Carbon $startAt = null;

    /**
     * @var ?Carbon $endAt
     */
    private ?Carbon $endAt = null;

    /**
     * Get the recurrence rule instance
     */
    public function getRruleInstance(): RRule
    {
        return new RRule($this->rrule);
    }

    /**
     * Set $startAt
     *
     * @param string $startAt
     */
    public function startAt(string $date): self
    {
        if (!$this->isValidDate($date)) {
            throw new RruleException('Please provide a valid start date');
        }

        if (filled($this->endAt) && ($date > $this->endAt)) {
            throw new RruleException('Start at should not be greater than end at');
        }

        $this->startAt = Carbon::make($date, request()->timezone());

        $this->rrule['DTSTART'] = $this->startAt->copy()
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d\TH:i:s\Z');

        return $this;
    }

    /**
     * Set $endAt
     *
     * @param string $endAt
     */
    public function endAt(string $date): self
    {
        if (isset($this->rrule['COUNT'])) {
            throw new RruleException("You can't set ends at if you already set the repeat rule.");
        }

        if (!$this->isValidDate($date)) {
            throw new RruleException('Please provide a valid end date');
        }

        if (filled($this->startAt) && ($date < $this->startAt)) {
            throw new RruleException('End at should not be less than start at');
        }

        $this->endAt = Carbon::make($date, request()->timezone());

        $this->rrule['UNTIL'] = $this->endAt->copy()
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d\TH:i:s\Z');

        return $this;
    }

    /**
     * Set the rrule COUNT
     *
     * @param int $count
     */
    public function repeat(int $count)
    {
        if (filled($this->endAt)) {
            throw new RruleException("You ca'nt set repeat if you already set the ends at rule.");
        }

        if ($count <= 0) {
            throw new RruleException("Invalid repeat rule. Repeat value must be greater than zero.");
        }

        $this->rrule['COUNT'] = $count;

        return $this;
    }

    /**
     * Check if date is valid
     *
     * @param string $date
     */
    public function isValidDate(string $date, $format = 'Y-m-d'): bool
    {
        $d = Carbon::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }

    /**
     * Monthly recurrence rule
     *
     * @param array<int> $byMonthDay
     * @param array $byDay
     * @param int $interval
     * @param int $repeatCount
     */
    public function monthly($byMonthDay = [1], $byDay = [], $interval = 1): self
    {
        $this->rrule['FREQ'] = 'MONTHLY';
        $this->rrule['INTERVAL'] = $interval;


        if (filled($byMonthDay)) {
            $this->rrule['BYMONTHDAY'] = implode(',', $byMonthDay);
            return $this;
        }

        if (filled($byDay)) {
            $this->rrule['BYDAY'] = implode(',', $byDay);
            return $this;
        }

        return $this;
    }

    /**
     * Daily recurrence rule
     *
     * @param array $byDay
     * @param int $interval
     */
    public function daily($byDay = [], int $interval = 1): self
    {
        $this->rrule['FREQ'] = 'DAILY';
        $this->rrule['INTERVAL'] = $interval;

        if (filled($byDay)) {
            $this->rrule['BYDAY'] = implode(',', $byDay);
        }

        return $this;
    }

    /**
     * Weekly recurrence rule
     *
     * @param array $byDay
     * @param int $interval
     */
    public function weekly($byDay = [], int $interval = 1): self
    {
        $this->rrule['FREQ'] = 'WEEKLY';
        $this->rrule['INTERVAL'] = $interval;

        if (filled($byDay)) {
            $this->rrule['BYDAY'] = implode(',', $byDay);
        }

        return $this;
    }

    /**
     * Yearly recurrence rule
     *
     * @param array $byMonth
     * @param array $byMonthDay
     * @param array $byDay
     * @param int $interval
     */
    public function yearly($byMonth = [], $byMonthDay = [], $byDay = [], int $interval = 1): self
    {
        $this->rrule['FREQ'] = 'YEARLY';
        $this->rrule['INTERVAL'] = $interval;

        if (filled($byMonth)) {
            $this->rrule['BYMONTH'] = implode(',', $byMonth);
        }

        if (filled($byMonthDay)) {
            $this->rrule['BYMONTHDAY'] = implode(',', $byMonthDay);
        }

        if (filled($byDay)) {
            $this->rrule['BYDAY'] = implode(',', $byDay);
        }

        return $this;
    }
}
