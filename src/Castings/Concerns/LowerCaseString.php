<?php

namespace R64\ContentImport\Castings\Concerns;

use Closure;

class LowerCaseString implements ValidationConcern
{
    public function handle($content, Closure $next)
    {
        $content = strtolower($content);

        return $next($content);
    }
}
