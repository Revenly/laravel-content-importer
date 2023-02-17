<?php

namespace R64\ContentImport\Castings\Concerns;

use Closure;
use R64\ContentImport\Pipelines\HandlerContract;

class TrimString implements HandlerContract
{
    public function handle($content, Closure $next)
    {
        $content = trim((string) $content);

        return $next($content);
    }
}
