<?php

namespace Calendar\Builder;

use Calendar\Enums\ReminderType;
use Calendar\Exceptions\InvalidCalendarable;
use Calendar\Exceptions\InvalidReminderType;
use Calendar\Exceptions\RruleException;
use Calendar\Jobs\GenerateCalendarEventReminders;
use Calendar\Models\CalendarEvent;
use Illuminate\Database\Eloquent\Model;

class CalendarEventBuilder
{
    use RecurrenceRuleBuilder;

    /**
     * @var Model $model
     */
    private Model $model;

    /**
     * @var string $title
     */
    private string $title = 'No Title';

    /**
     * @var string $description
     */
    private string $description = '';

    /**
     * @var bool $isWholeDay
     */
    private bool $isWholeDay = false;

    /**
     * @var array $metaData
     */
    private array $metaData = [];

    /**
     * @var array $reminders
     */
    private array $reminders = [
        ['type' => 'minutes', 'nth' => 10]
    ];

    /**
     * @var string $startTime
     */
    private string $startTime = '00:00';

    /**
     * @var string $endTime
     */
    private string $endTime = '23:59';

    /**
     * Create new ScheduleBuilder instance
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        if (!$model->id) {
            throw new InvalidCalendarable();
        }

        $this->model = $model;
    }

    /**
     * Set the calendarable model for this schedule
     *
     * @param Model $model
     */
    public static function for(Model $model): self
    {
        return new self($model);
    }

    /**
     * Set the event title
     *
     * @param string $title
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the event description
     *
     * @param string $description
     */
    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the event is_whole_day flag
     *
     * @param bool $isWholeDay
     */
    public function isWholeDay(bool $isWholeDay = false): self
    {
        $this->isWholeDay = $isWholeDay;

        return $this;
    }

    /**
     * Set the event time
     *
     * @param string $start
     * @param string $end
     */
    public function time(string $start, string $end): self
    {
        if ($start >= $end) {
            throw new RruleException('Start time must not be greater than end time.');
        }

        $this->startTime = $start;
        $this->endTime = $end;

        return $this;
    }

    /**
     * Set meta data
     *
     * @param array $metaData
     */
    public function metaData(array $metaData): self
    {
        $this->metaData = $metaData;

        return $this;
    }

    /**
     * Set reminders
     *
     * @param array $reminders
     */
    public function reminders(array $reminders): self
    {
        foreach ($reminders as $key => $reminder) {
            if (!in_array($reminder['type'] ?? null, ReminderType::getValues())) {
                throw new InvalidReminderType();
            }

            if (!isset($reminder['nth'])) {
                $reminders[$key]['nth'] = ReminderType::tryFrom($reminder['type'])->defaultNth();
            }
        }

        $this->reminders = $reminders;

        return $this;
    }


    /**
     * Get the value of the specified attribute
     *
     * @param string $attribute
     */
    public function get(string $attribute)
    {
        return $this->{$attribute};
    }

    /**
     * Create the event
     *
     * @param string $calendar
     */
    public function create(string $calendarName = 'default'): CalendarEvent
    {
        $calendar = $this->model->calendar($calendarName);
        $startTime = $this->isWholeDay ? '00:00' : $this->startTime;
        $endTime = $this->isWholeDay ? '23:59' : $this->endTime;

        $eventData = [
            'title' => $this->title,
            'description' => $this->description,
            'is_whole_day' => $this->isWholeDay,
            'original_start_time' => $startTime,
            'original_end_time' => $endTime,
            'meta_data' => $this->metaData,
            'reminders' => $this->reminders,
            'timezone' => request()->timezone(),
        ];

        $rrule = $this->getRruleInstance();

        $eventData['is_infinite'] = $rrule->isInfinite();
        $eventData['rrule'] = $rrule->rfcString();
        $eventData['original_start_date'] = $this->startAt;

        if (filled($this->endAt)) {
            $eventData['original_end_date'] = $this->endAt;
            $eventData['utc_end_timestamp'] = $this->endAt->copy()
                ->setTimeFromTimeString($endTime)
                ->setTimezone(config('app.timezone'));
        } else {
            $eventData['utc_end_timestamp'] = $this->startAt->copy()
                ->setTimeFromTimeString($endTime)
                ->setTimezone(config('app.timezone'));
        }

        $eventData['utc_start_timestamp'] = $this->startAt->copy()
            ->setTimeFromTimeString($startTime)
            ->setTimezone(config('app.timezone'));

        $eventData['utc_offset'] = $this->startAt->copy()->format('p');

        $event = $calendar->events()->create($eventData);

        if (config('calendar.allow_reminder')) {
            dispatch(new GenerateCalendarEventReminders($event));
        }

        return $event;
    }
}
