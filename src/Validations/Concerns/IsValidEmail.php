<?php

namespace R64\ContentImport\Validations\Concerns;

use Closure;
use R64\ContentImport\Events\ValidationFailed;
use R64\ContentImport\Exceptions\ValidationFailedException;
use R64\ContentImport\Pipelines\HandlerContract;

class IsValidEmail implements HandlerContract
{
    public function handle($content, Closure $next)
    {
        if (!filter_var($content, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationFailedException("Email is not valid");
        }

        return $next($next);
    }
}
