<?php

namespace Calendar\Models\Traits;

use Calendar\Models\Calendar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use RRule\RRule;

trait InteractsWithCalendar
{
    /**
     * Get the model's calendars.
     */
    public function calendars(): MorphMany
    {
        return $this->morphMany(Calendar::class, 'calendarable');
    }

    /**
     * Get specified calendar
     *
     * @param string $name
     */
    public function calendar($name = 'default'): Calendar
    {
        $record =  $this->calendars()->whereName($name)->first();

        if (!$record) {
            $record = $this->calendars()->create([
                'name' => $name,
                'color' => sprintf("#%06X", mt_rand(0, 0xFFFFFF)),
            ]);
        }

        return $record;
    }

    /**
     * Get the events of the specified calendar
     *
     * @param array $filters
     * @param string $calendar
     */
    public function calendarEvents(array $filters = [], string $calendar = 'default'): Collection
    {
        $startDate = $this->getStartDate($filters['start_date'] ?? null);

        $endDate = $this->getEndDate($filters['end_date'] ?? null);

        $finiteEvents = $this->calendar($calendar)
            ->events()
            ->where('utc_start_timestamp', '<=', $endDate)
            ->where('utc_end_timestamp', '>=', $startDate)
            ->where('is_infinite', false);

        $infiniteEvents = $this->calendar($calendar)
            ->events()
            ->where('is_infinite', true);

        $calendarEvents = $finiteEvents->unionAll($infiniteEvents)
            ->when(isset($filters['search']), function ($query) use ($filters) {
                $query->where('title', 'like', "%" . $filters['search'] . "%");
            })
            ->get();

        $events = collect([]);
        $timezone = request()->timezone();

        foreach ($calendarEvents as $calendarEvent) {
            $rrule = new RRule($calendarEvent->rrule);

            $occurrences = $rrule->getOccurrencesBetween($startDate, $endDate);

            foreach ($occurrences as $occurrence) {
                $utcStartTime = $calendarEvent->utc_start_timestamp->format('H:i:s');
                $utcEndTime = $calendarEvent->utc_end_timestamp->format('H:i:s');

                $start = Carbon::make($occurrence->format('Y-m-d H:i:s'))
                    ->setTimeFromTimeString($utcStartTime)
                    ->setTimezone($timezone);

                $end = Carbon::make($occurrence->format('Y-m-d H:i:s'))
                    ->setTimeFromTimeString($utcEndTime)
                    ->setTimezone($timezone);

                $event = [
                    'id' => $calendarEvent->id,
                    'start_date' => $start,
                    'end_date' => $end,
                    'title' => $calendarEvent->title,
                    'description' => $calendarEvent->description,
                    'is_whole_day' => $calendarEvent->is_whole_day,
                ];

                $events->push($event);
            }
        }

        return $events->sortBy('start_date')->values();
    }

    /**
     * Get start date for filtering
     *
     * @param string $startDate
     */
    private function getStartDate(?string $startDate): string
    {
        $timezone = request()->timezone();

        if (filled($startDate)) {
            $date = Carbon::make($startDate, $timezone)
                ->startOfDay()
                ->setTimezone(config('app.timezone'))
                ->format('Y-m-d H:i:s');

            return $date;
        }

        return now($timezone)
            ->startOfMonth()
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');
    }

    /**
     * Get end date for filtering
     *
     * @param string $endDate
     */
    private function getEndDate(?string $endDate): string
    {
        $timezone = request()->timezone();

        if (filled($endDate)) {
            $date = Carbon::make($endDate, $timezone)
                ->endOfDay()
                ->setTimezone(config('app.timezone'))
                ->format('Y-m-d H:i:s');

            return $date;
        }

        return now($timezone)
            ->endOfMonth()
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');
    }
}
