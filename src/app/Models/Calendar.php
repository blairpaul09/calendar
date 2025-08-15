<?php

namespace Calendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Calendar extends Model
{
    protected $fillable = [
        'calendarable_type',
        'calendarable_id',
        'name',
        'color',
    ];

    /**
     * Get the parent calendarable model.
     */
    public function calendarable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the calendar's events
     */
    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }
}
