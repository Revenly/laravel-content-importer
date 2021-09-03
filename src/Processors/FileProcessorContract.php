<?php

namespace R64\ContentImport\Processors;


interface FileProcessorContract
{
    public function read(string $path, ?string $delimeter);
}
