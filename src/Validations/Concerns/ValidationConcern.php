<?php

namespace R64\ContentImport\Validations\Concerns;

use Closure;

interface ValidationConcern
{
    public function handle($content, Closure $next);
}
