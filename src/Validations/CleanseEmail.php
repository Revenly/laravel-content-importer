<?php

namespace R64\ContentImport\Validations;

use Illuminate\Pipeline\Pipeline;
use R64\ContentImport\Validations\Concerns\LowerCaseString;
use R64\ContentImport\Validations\Concerns\RemoveInvalidCharacters;
use R64\ContentImport\Validations\Concerns\TrimString;

class CleanseEmail extends ValidationPipeline
{
    public $concerns = [
        TrimString::class,
        LowerCaseString::class,
        RemoveInvalidCharacters::class
    ];
}
