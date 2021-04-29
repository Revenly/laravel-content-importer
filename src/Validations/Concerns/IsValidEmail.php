<?php

namespace R64\ContentImport\Validations\Concerns;

use Closure;
use R64\ContentImport\Events\ValidationFailed;
use R64\ContentImport\Exceptions\ValidationFailedException;
use R64\ContentImport\Pipelines\HandlerContract;

class IsValidEmail
{
    public function __invoke($content)
    {
        return filter_var($content, FILTER_VALIDATE_EMAIL);
    }
}
