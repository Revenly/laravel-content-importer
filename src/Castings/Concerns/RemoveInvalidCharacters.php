<?php
namespace R64\ContentImport\Castings\Concerns;

use Closure;

class RemoveInvalidCharacters implements ValidationConcern
{
    public function handle($content, Closure $next)
    {
        $content = preg_replace('/[^A-Za-z0-9-_@.]/', '', $content);

        return $next($content);
    }
}
