<?php

namespace R64\ContentImport\Castings\Concerns;

use Closure;

interface ValidationConcern
{
    public function handle($content, Closure $next);
}
