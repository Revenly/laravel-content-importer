<?php

namespace R64\ContentImport\Validations;
use R64\ContentImport\Pipelines\Pipeline;
use R64\ContentImport\Validations\Concerns\IsValidEmail;

class ValidEmail extends Pipeline
{
    public $concerns = [
        IsValidEmail::class
    ];
}
