<?php

namespace R64\ContentImport\Castings;

use Illuminate\Pipeline\Pipeline;
use R64\ContentImport\Castings\Concerns\LowerCaseString;
use R64\ContentImport\Castings\Concerns\RemoveInvalidCharacters;
use R64\ContentImport\Castings\Concerns\TrimString;

class CleanseEmail extends CastingPipeline
{
    public $concerns = [
        TrimString::class,
        LowerCaseString::class,
        RemoveInvalidCharacters::class
    ];
}
