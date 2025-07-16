<?php

namespace App\Exceptions\Auth;

use Exception;

class UserNotFoundException extends Exception
{
    public function __construct(string $message = 'User not found')
    {
        parent::__construct($message);
    }
}
