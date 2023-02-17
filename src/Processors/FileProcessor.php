<?php

namespace R64\ContentImport\Processors;

use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class FileProcessor implements FileProcessorContract
{
    // When delimiter is not found, csv is returned as 1 column
    // same thing when you have just 1 column
    public function read(string $path)
    {
        $delimiter = $this->findDelimiter($path);

        $stream = fopen(Storage::disk('local')->path($path), 'r');
        $processor = Reader::createFromStream($stream);

        $processor->setHeaderOffset(0);
        $processor->setDelimiter($delimiter);

        $headers = array_map(fn($header) => strtolower(str_replace(' ', '', (string) $header)), $processor->getHeader());

        return $processor->getRecords($headers);
    }

    protected function findDelimiter(string $path): false|int|string
    {
        $stream = fopen(Storage::disk('local')->path($path), 'r');

        $supportedDelimiters = config('content_import.supported_delimiters', [';', ',', '|', '\t']);

        $delimiters = array_combine(
            $supportedDelimiters,
            array_fill(0, count($supportedDelimiters), 0)
        );

        $firstLine = fgets($stream);
        fclose($stream);
        foreach ($delimiters as $delimiter => &$count) {
            $count = count(str_getcsv($firstLine, $delimiter));
        }

        return array_search(max($delimiters), $delimiters);
    }
}
