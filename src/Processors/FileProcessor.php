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
        $delimiter = $this->findDelimiter($path);

        $stream = fopen(Storage::disk('local')->path($path), 'r');
        $processor = Reader::createFromStream($stream);

        $processor->setHeaderOffset(0);
        $processor->setDelimiter($delimiter);

        $headers = array_map(function ($header) {
            return strtolower(str_replace(' ', '', $header));
        }, $processor->getHeader());

        return $processor->getRecords($headers);
    }

    /**
     * @param string $path
     *
     * @return false|int|string
     */
    protected function findDelimiter(string $path)
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
