<?php

namespace R64\ContentImport\Validations;

use Illuminate\Pipeline\Pipeline;
use R64\ContentImport\Validations\Concerns\LowerCaseString;
use R64\ContentImport\Validations\Concerns\TrimString;

class CleanseEmail
{
    public $concerns = [
        TrimString::class,
        LowerCaseString::class
    ];

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function __invoke(): string
    {
        return app(Pipeline::class)
            ->send($this->email)
            ->through($this->concerns)
            ->then(function ($email) {
                return $email;
            });
    }
}
