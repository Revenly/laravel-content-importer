<?php
namespace R64\ContentImport\Validations;

use Closure;
use Exception;
use R64\ContentImport\Castings\Concerns\ValidationConcern;
use R64\ContentImport\Events\ValidationFailed;
use R64\ContentImport\Exceptions\ValidationFailedException;

class IsNotNull implements ValidationConcern
{
    public function handle($content, Closure $next)
    {
        if (is_null($content)) {
            ValidationFailed::dispatch($content);
            throw new ValidationFailedException(self::class ." failed for ". $content);
        }
        return $next($next);
    }
}
