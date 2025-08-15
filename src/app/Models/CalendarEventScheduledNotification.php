<?php

namespace Calendar\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEventScheduledNotification extends Model
{
    protected $fillable = [
        'calendar_event_id',
        'title',
        'body',
        'exception',
        'scheduled_at',
    ];
}
