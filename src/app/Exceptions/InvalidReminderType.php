<?php

namespace Calendar\Exceptions;

use Calendar\Enums\ReminderType;
use Exception;

class InvalidReminderType extends Exception
{
    public function __construct(string $message = "Please provide a valid reminder type.", int $code = 400)
    {
        $message .= ' types: ' . implode(', ', ReminderType::getValues());

        parent::__construct($message, $code);
    }
}
