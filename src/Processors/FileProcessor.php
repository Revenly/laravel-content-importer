<?php

namespace R64\ContentImport\Processors;

use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Statement;
use SplFileObject;

class FileProcessor implements FileProcessorContract
{

    public function read(string $path, ?string $delimeter)
    {
        $stream = fopen(Storage::disk('local')->path($path), 'r');

        $processor = Reader::createFromStream($stream);

        $processor->setHeaderOffset(0);
        $processor->setDelimiter($delimeter);

        $headers = array_map(function ($header) {
            return str_replace(' ', '', $header);
        }, $processor->getHeader());

        return $processor->getRecords($headers);
    }
}
