<?php

namespace App\Exception;

use Exception;

class ValidationException extends Exception
{
    private array $details;

    public function __construct(array $details, string $message = 'Validation failed')
    {
        parent::__construct($message);
        $this->details = $details;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}