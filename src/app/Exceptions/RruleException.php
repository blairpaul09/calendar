<?php

namespace Calendar\Exceptions;

use Exception;

class RruleException extends Exception
{
    public function __construct(string $message = "Please provide a valid date", int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
