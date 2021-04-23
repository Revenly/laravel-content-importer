<?php
namespace R64\ContentImport\Validations;

interface ValidationPipeContract
{
    public function __invoke($content, array $concerns = []);

    public function pipe($content);
}
