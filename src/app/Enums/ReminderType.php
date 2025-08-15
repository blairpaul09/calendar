<?php

namespace Calendar\Enums;

enum ReminderType: string
{
    case HOURS = 'hours';
    case MINUTES = 'minutes';
    case DAYS = 'days';
    case WEEKS = 'weeks';

    /**
     * Get values as array
     */
    public static function getValues(): array
    {
        return  array_map(fn($case) => $case->value, ReminderType::cases());
    }

    /**
     * Get default nth of reminder type
     */
    public function defaultNth(): int
    {
        return match ($this) {
            self::HOURS => 1,
            self::MINUTES => 10,
            self::DAYS => 1,
            self::WEEKS => 1,
            default => 1
        };
    }
}
