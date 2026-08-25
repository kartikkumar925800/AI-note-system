<?php
namespace App\Exceptions;

use Exception;

class AIProviderException extends Exception
{
    public array $details;

    public function __construct(string $message, array $details = [])
    {
        parent::__construct($message);
        $this->details = $details;
    }
}
