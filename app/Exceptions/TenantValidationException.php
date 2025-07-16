<?php

namespace App\Exceptions;

use Exception;

class TenantValidationException extends Exception
{
    public function __construct(string $message = 'Tenant validation failed', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
