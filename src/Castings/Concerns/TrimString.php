<?php

namespace R64\ContentImport\Castings\Concerns;

use Closure;

class TrimString implements ValidationConcern
{
    public function handle($content, Closure $next)
    {
        $content = trim($content);

        return $next($content);
    }
}
