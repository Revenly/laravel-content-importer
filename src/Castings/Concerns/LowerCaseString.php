<?php

namespace R64\ContentImport\Castings\Concerns;

use Closure;
use R64\ContentImport\Pipelines\HandlerContract;

class LowerCaseString implements HandlerContract
{
    public function handle($content, Closure $next)
    {
        $content = strtolower((string) $content);

        return $next($content);
    }
}
