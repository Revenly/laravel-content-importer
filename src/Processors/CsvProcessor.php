<?php

namespace R64\ContentImport\Processors;

use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class CsvProcessor implements FileProcessorContract
{
    public function read(string $path, string $delimeter = null)
    {
        $stream = fopen(Storage::disk('local')->path($path), 'r');
        $csv = Reader::createFromStream($stream);

        if (config('content_import.heading_row', true)) {
            $csv->setHeaderOffset(0);
        }

        return $csv->getRecords();
    }
}
