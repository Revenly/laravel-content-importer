<?php

namespace R64\ContentImport\Validations\Concerns;

use Closure;

class TrimString implements ValidationConcern
{
    public function handle($content, Closure $next)
    {
        $content = trim($content);

        return $next($content);
    }
}
