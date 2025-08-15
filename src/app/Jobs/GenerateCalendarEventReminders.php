<?php

namespace Calendar\Jobs;

use Calendar\Enums\ReminderType;
use Calendar\Models\CalendarEvent;
use Calendar\Models\CalendarEventScheduledNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RRule\RRule;

class GenerateCalendarEventReminders implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private CalendarEvent $calendarEvent)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $rrule = new RRule($this->calendarEvent->rrule);

        $startDate = now(config('app.timezone'))->format('Y-m-d');
        $endDate = now(config('app.timezone'))->endOfMonth()->format('Y-m-d');

        $occurrences = $rrule->getOccurrencesBetween($startDate, $endDate);

        foreach ($occurrences as $occurrence) {
            $utcStartTime = $this->calendarEvent->utc_start_timestamp->format('H:i:s');

            $date = Carbon::make($occurrence->format('Y-m-d H:i:s'))
                ->setTimeFromTimeString($utcStartTime);

            foreach ($this->calendarEvent->reminders as $reminder) {
                $schedule = $this->getSchedule($date->copy(), $reminder);
                if ($schedule > now()) {
                    CalendarEventScheduledNotification::create([
                        'calendar_event_id' => $this->calendarEvent->id,
                        'title' => $this->calendarEvent->title,
                        'body' => $this->calendarEvent->description,
                        'scheduled_at' => $schedule,
                    ]);
                }
            }
        }
    }

    /**
     * Get schedule
     *
     * @param Carbon $date
     * @param array $reminder
     */
    private function getSchedule(Carbon $date, array $reminder): Carbon
    {
        return match (ReminderType::tryFrom($reminder['type'])) {
            ReminderType::MINUTES => $date->subMinutes($reminder['nth']),
            ReminderType::HOURS => $date->subHours($reminder['nth']),
            ReminderType::DAYS => $date->subDays($reminder['nth']),
            ReminderType::WEEKS => $date->subWeeks($reminder['nth']),
            default => $date->subMinutes($reminder['nth']),
        };
    }
}
