<?php

namespace R64\ContentImport\Validations;

use Illuminate\Pipeline\Pipeline;
use R64\ContentImport\Validations\Concerns\LowerCaseString;
use R64\ContentImport\Validations\Concerns\RemoveInvalidCharacters;
use R64\ContentImport\Validations\Concerns\TrimString;

class ValidationPipeline implements ValidationPipeContract
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

        return app(Pipeline::class)
            ->send($content)
            ->through($concerns)
            ->then(function ($content) {
                return $content;
            });
    }
}
