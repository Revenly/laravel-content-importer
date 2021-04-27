<?php
namespace R64\ContentImport\Exceptions;

use Exception;

class ValidationFailedException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
