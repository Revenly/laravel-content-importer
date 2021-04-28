<?php

namespace R64\ContentImport\Pipelines;

use Closure;

interface HandlerContract
{
    public function handle($content, Closure $next);
}
