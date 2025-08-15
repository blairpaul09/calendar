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

This will create a daily recurring event until the set end date. For infinite events you can just remove `->endAt($endAt)`. if your config for `allow_reminder` is set to true, it will automatically generate a notification schedules, see `calendar_event_scheduled_notifications`. It's up to you on how you manage the notification schedule.

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
  sample reminders

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
