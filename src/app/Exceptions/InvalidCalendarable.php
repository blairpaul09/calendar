<?php

namespace Calendar\Exceptions;

use Exception;

class InvalidCalendarable extends Exception
{
    public function __construct(string $message = "Please provide a valid calendarable model.", int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
