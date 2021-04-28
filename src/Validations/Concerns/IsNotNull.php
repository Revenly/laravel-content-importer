<?php
namespace R64\ContentImport\Validations\Concerns;

use Closure;
use R64\ContentImport\Events\ValidationFailed;
use R64\ContentImport\Exceptions\ValidationFailedException;
use R64\ContentImport\Pipelines\HandlerContract;

class IsNotNull implements HandlerContract
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
