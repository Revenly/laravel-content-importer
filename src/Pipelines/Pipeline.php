<?php

namespace R64\ContentImport\Pipelines;

use Illuminate\Pipeline\Pipeline as LaravelPipeline;

class Pipeline implements PipelineContract
{
    public function __invoke($content, array $concerns = [])
    {
        return $this->pipe($content, $concerns);
    }

    public function pipe($content, array $concerns = [])
    {
        if (property_exists($this, 'concerns')) {
            $concerns = $this->concerns;
        }

        return app(LaravelPipeline::class)
            ->send($content)
            ->through($concerns)
            ->then(fn($content) => $content);
    }
}
