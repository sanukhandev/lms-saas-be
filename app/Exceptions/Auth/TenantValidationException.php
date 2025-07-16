<?php

namespace App\Exceptions\Auth;

use Exception;

class TenantValidationException extends Exception
{
    public function __construct(string $message = 'Tenant validation failed')
    {
        parent::__construct($message);
    }
}
