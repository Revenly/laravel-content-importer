<?php
namespace R64\ContentImport\Castings\Concerns;

use Closure;
use R64\ContentImport\Pipelines\HandlerContract;

class RemoveInvalidCharacters implements HandlerContract
{
    public function handle($content, Closure $next)
    {
        $content = preg_replace('/[^A-Za-z0-9-_@.]/', '', $content);

        return $next($content);
    }
}
