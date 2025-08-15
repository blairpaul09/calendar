<?php

namespace Calendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CalendarEvent extends Model
{
    protected $fillable = [
        'calendar_id',
        'title',
        'description',
        'original_start_date',
        'original_end_date',
        'original_start_time',
        'original_end_time',
        'utc_start_timestamp',
        'utc_end_timestamp',
        'timezone',
        'utc_offset',
        'is_whole_day',
        'is_infinite',
        'meta_data',
        'reminders',
        'rrule',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_whole_day' => 'boolean',
            'is_infinite' => 'boolean',
            'meta_data' => 'json',
            'reminders' => 'array',
            'utc_start_timestamp' => 'datetime',
            'utc_end_timestamp' => 'datetime',
        ];
    }

    /**
     * Get the events's calendar
     */
    public function calendar(): HasOne
    {
        return $this->hasOne(Calendar::class, 'id', 'calendar_id');
    }
}
