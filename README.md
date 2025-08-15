# Calendar

A simple Laravel package providing calendar and recurring event features using [`rlanvin/php-rrule`](https://github.com/rlanvin/php-rrule), a lightweight and fast recurrence rules library for PHP (RFC 5545).

## Requirements

- PHP >= 8.3
- Laravel >= 11
- [`rlanvin/php-rrule`](https://github.com/rlanvin/php-rrule)

## Installation

```bash
composer require blairpaul09/calendar
```

## Publish configuration

```bash
php artisan vendor:publish --provider="Calendar\Providers\CalendarServiceProvider"
```

This will publish the config file of the calendar module and the migrations.
// config/calendar.php

```php
<?php
return [
    'allow_reminder' => env('CALENDAR_ALLOW_REMINDER', true),
];
```

## Migration

```bash
php artisan migrate
```

## User setup

Use the `Calendar\Models\Traits\InteractsWithCalendar` trait into your user model.

```php
<?php

namespace App\Models;

use Calendar\Models\Traits\InteractsWithCalendar;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use InteractsWithCalendar;
    //
}
```

## Sample Usage

```php
$user = User::find(1);
$timezone = 'Asia/Manila';
$start = now($timezone)->format('Y-m-d');
$end = now($timezone)->addDays(10)->format('Y-m-d');

$event = CalendarEventBuilder::for($user)
    ->startAt($start)
    ->endAt($end)
    ->time('13:00', '14:00')
    ->daily()
    ->reminders([
        ['type' => 'minutes', 'nth' => 10],
        ['type' => 'hours', 'nth' => 1],
        ['type' => 'days', 'nth' => 1],
        ['type' => 'weeks', 'nth' => 1],
    ])
    ->create();
```

This will create a daily recurring event until the set end date. For infinite events you can just remove `->endAt($endAt)`. if your config for `allow_reminder` is set to `true`, it will automatically generate a scheduled notifications, see `calendar_event_scheduled_notifications` DB table. It's up to you on how you will handle the scheduled notifications.

## Sample scheduler to process scheduled notifications.

```bash
php artisan make:command ProcessScheduledNotifications
```

```php
<?php

namespace App\Console\Commands;

use Calendar\Models\CalendarEventScheduledNotification;
use Illuminate\Console\Command;

class ProcessScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-scheduled-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach ($this->getNotifications() as $notification) {
            try {

                //your notification process here

                //sample, sending email notification to the users

                $notification->delete();
            } catch (\Exception $e) {
                $exception = json_encode([
                    'mnessage' => $e->getMessage(),
                    'trace' => $e->getTrace()
                ], JSON_PRETTY_PRINT);
                $notification->update(['exception' => $exception]);
            }
        }
    }

    /**
     * Get notifications
     */
    private function getNotifications()
    {
        $date = now();

        $notifications =  CalendarEventScheduledNotification::where('scheduled_at', '<=', $date->format('Y-m-d H:i:00'))
            ->orderBy('scheduled_at')
            ->whereNull('exception')
            ->get();

        return $notifications;
    }
}
```

And register the command to `route/console.php` by adding the code below.

```php
Schedule::command('app:process-scheduled-notifications')->monthlyOn(1, '00:00');
```

## Retrieving calendar events

```php
$user = User::find(1);
$start = '2025-07-17';
$end = '2025-07-27';

$events = $user->calendarEvents(['start_date' => $start, 'end_date' => $end]);

return $events;
```

## The CalendarEventBuilder

- `for($user)` Required: the method to set which user will the calendar event to be created.

- `->startAt($startDate)` Required: the method to set the start date of the event.

- `->endAt($endDate)` Optional: the method to set the end date of the event.

- `->time('13:00', '14:00')` Optional: the method to set the time duration of the event. If not set, the default is `00:00` to `23:59`

- `->reminders($reminders)` Optional: the method to set the scheduled notification reminders.

sample reminders:

```php
[
    ['type' => 'minutes', 'nth' => 10],
    ['type' => 'hours', 'nth' => 1],
    ['type' => 'days', 'nth' => 1],
    ['type' => 'weeks', 'nth' => 1],
]
```

- `->daily($byDay = [], int $interval = 1)` Daily frequency: This method is to set the event frequency to daily with optional parameters `$byDay` and `$interval`.

- `monthly($byMonthDay = [1], $byDay = [], $interval = 1)` Monthly frequency: This method is to set the event frequency to monthly with optional parameters `$byMonthDay`, `$byDay` and `$interval`.

- `weekly($byDay = [], int $interval = 1)` Weekly frequency: This method is to set the event frequency to weekly with optional parameters `$byDay` and `$interval`.

- `yearly($byMonth = [], $byMonthDay = [], $byDay = [], int $interval = 1)` Yearly frequency: This method is to set the event frequency to yearly with optional parameters `$byMonth`, `$byMonthDay`, `$byDay` and `$interval`.

- `->create()` The method to execute event creation. By default it will create a calendar named `default` if no parameter is set, if you wan't to add the event in another calendar, you can just simply pass the calendar name `->create($calendarName)`

### Frequency parameters values

```php
$interval = 1; # any integer.
$byDay = ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU']
$byMonthDay = [1, 2, 3...., 31] #Negative numbers: -1 to -31 → counting backward from the end of the month
// -1 = last day of the month
// -2 = second-to-last day of the month
// -7 = seventh-to-last day of the month
$byMonth = [1, 2, 3...., 12] # months by number
```

## Generate Calendar Event Reminders

Current this is being dispatch when creating and event `Calendar\Jobs\GenerateCalendarEventReminders`.

```php
use Calendar\Jobs\GenerateCalendarEventReminders;


$event = $calendar->events()->create($eventData);

if (config('calendar.allow_reminder')) {
    dispatch(new GenerateCalendarEventReminders($event));
}

```

For infinite events, you can create a scheduler to dispatch this Job every 1st of a month at 12:00 am to generate new scheduled reminders. But still, this is only optional if you wan't to implement reminders for your users.

## Working with Timezone

- By default it will follow the timezone from app config. `config('app.timezone')`

- If you are dealing with multiple timezone, make sure to add `timezone` in your request headers. Sample `timezone: Asia/Manila`.

- When retrieving calendar events, dates are automatically converted to the timezone from the request headers.

- If the timezone is not set to request header, the default will be the `config('app.timezone')`;

- Available helper to get timezone from header. `request()->timezone()` or `$request->timezone()`

```php
Request::macro('timezone', function () {
    return request()->header('timezone', config('app.timezone'));
});
```
