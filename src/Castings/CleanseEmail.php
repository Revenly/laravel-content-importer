<?php

namespace R64\ContentImport\Castings;

use R64\ContentImport\Castings\Concerns\LowerCaseString;
use R64\ContentImport\Castings\Concerns\RemoveInvalidCharacters;
use R64\ContentImport\Castings\Concerns\TrimString;
use R64\ContentImport\Pipelines\Pipeline;

class CleanseEmail extends Pipeline
{
    public $concerns = [
        TrimString::class,
        LowerCaseString::class,
        RemoveInvalidCharacters::class
    ];
}
